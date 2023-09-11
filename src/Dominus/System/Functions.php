<?php

use Dominus\Services\Validator\Validator;
use Dominus\System\Attributes\DataModel\Optional;
use Dominus\System\Attributes\DataModel\TrimString;
use Dominus\System\Attributes\DataModel\Validate;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Models\LogType;

/**
 * Adds a new log entry to the log file stored in the Logs directory
 */
function _log(string $message, LogType $type): void
{
    AppConfiguration::log($message, $type);
}

/**
 * @param string $key
 * @param string|int $default Optional default value if the key is not found
 * @return string
 */
function env(string $key, string|int $default = ''): string
{
    return $_SERVER[$key] ?? $default;
}

/**
 * Attempts to map an array/object to a destination array/object
 * @param array | object $source
 * @param array | object | null $destination
 * @param bool $errorOnMismatch Will throw AutoMapPropertyMismatch if the source and destination does not have matching properties.
 * @param bool $autoValidate Validate properties that have the #[Validate()] attribute
 *
 * @return array|object
 *
 * @throws AutoMapPropertyInvalidValue
 * @throws AutoMapPropertyMismatchException
 * @throws ReflectionException
 */
function autoMap(array | object $source, array | object | null $destination, bool $errorOnMismatch = true, bool $autoValidate = true): array | object
{
    if(is_null($destination))
    {
        return $source;
    }

    $sourceIsObject = is_object($source);

    if(!is_object($destination))
    {
        if(empty($source))
        {
            return $destination;
        }

        if(empty($destination))
        {
            return $source;
        }

        foreach ($destination as $destinationKey)
        {
            $sourceValue = $sourceIsObject ? ($source->$destinationKey ?? null) : ($source[$destinationKey] ?? null);
            if(!is_null($sourceValue))
            {
                $destination[$destinationKey] = is_array($destination[$destinationKey]) ? autoMap($sourceValue, $destination[$destinationKey], $errorOnMismatch, $autoValidate) : $sourceValue;
            }
        }

        return $destination;
    }

    $destRef = new ReflectionClass($destination);
    $destProperties = $destRef->getProperties(ReflectionProperty::IS_PUBLIC);

    if(!$source)
    {
        if(!$destProperties)
        {
            return $destination;
        }
        else if ($errorOnMismatch)
        {
            throw new AutoMapPropertyMismatchException('Error mapping model ['.$destination::class.']: Empty source!');
        }
    }

    $validator = $autoValidate ? new Validator() : null;

    foreach($destProperties as $destPropRef)
    {
        $destProp = $destPropRef->getName();
        $destPropOptional = (bool)$destPropRef->getAttributes(Optional::class);
        $destPropType = $destPropRef->getType();
        $destPropAllowsNull = !$destPropType || $destPropType->allowsNull();
        $srcPropValue = $sourceIsObject ? ($source->$destProp ?? null) : ($source[$destProp] ?? null);
        $srcPropValueIsString = is_string($srcPropValue);

        if(is_null($srcPropValue))
        {
            if($destPropOptional)
            {
                continue;
            }
            else if($destPropAllowsNull)
            {
                $destination->$destProp = null;
                continue;
            }
            else if($errorOnMismatch)
            {
                throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Missing source property [$destProp]");
            }
        }

        if($srcPropValue && $srcPropValueIsString && $destPropRef->getAttributes(TrimString::class))
        {
            $srcPropValue = trim($srcPropValue);
        }

        if($propValidationAttribute = $destPropRef->getAttributes(Validate::class))
        {
            $propValidationAttribute = $propValidationAttribute[0];
            try
            {
                $validator->validate(
                    [$destProp => $srcPropValue],
                    [$destProp => rtrim($propValidationAttribute->getArguments()[0], '|')],
                    true
                );
            }
            catch (Exception $e)
            {
                throw new AutoMapPropertyInvalidValue('Validation failed: ' . $e->getMessage());
            }
        }

        // assign data from source directly if the destination type is not known
        if(!$destPropType)
        {
            $destination->$destProp = $srcPropValue;
            continue;
        }

        $destPropTypeName = '';

        if(!($destPropType instanceof ReflectionNamedType))
        {
            $unionTypes = $destPropType->getTypes();
            foreach($unionTypes as $unionType)
            {
                if(!$unionType->isBuiltin())
                {
                    $destPropTypeName = $unionType->getName();
                    if($destPropTypeName === 'stdClass')
                    {
                        $destPropTypeName = '';
                    }
                    break;
                }
            }
        }
        else
        {
            $destPropTypeName = $destPropType->getName();
            if($destPropType->isBuiltin())
            {
                if($errorOnMismatch)
                {
                    switch ($destPropTypeName)
                    {
                        case 'bool':
                            $destPropTypeName = 'boolean';
                            break;

                        case 'int':
                            $destPropTypeName = 'integer';
                            break;

                        case 'float':
                            $destPropTypeName = 'double';
                            break;
                    }

                    $srcDataType = gettype($srcPropValue);
                    if($destPropTypeName !== $srcDataType)
                    {
                        throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Property type mismatch [$destProp]! Expected [$destPropTypeName] got [$srcDataType].");
                    }
                }

                $destPropTypeName = '';
            }
        }

        $srcPropIterable = is_array($srcPropValue) || is_object($srcPropValue);

        if($destPropTypeName !== '')
        {
            if($destPropTypeName === 'DateTime' || $destPropTypeName === 'DateTimeImmutable')
            {
                try
                {
                    $destInstance = new $destPropTypeName($srcPropValue);
                    $srcPropIterable = false;
                }
                catch (Exception)
                {
                    throw new AutoMapPropertyInvalidValue('Error mapping model ['.$destination::class.']: Failed to construct [' . $destPropTypeName . '] from value [' . $srcPropValue . ']');
                }
            }
            else if(enum_exists($destPropTypeName))
            {
                /**
                 * @var $destPropTypeName BackedEnum
                 */
                $destInstance = $destPropTypeName::tryFrom($srcPropValue);
                $srcPropIterable = false;

                if(is_null($destInstance) && !$destPropAllowsNull)
                {
                    throw new AutoMapPropertyInvalidValue('Error mapping model ['.$destination::class.']: Failed to construct enum [' . $destPropTypeName . '] from value [' . $srcPropValue . ']');
                }
            }
            else
            {
                $destInstance = new $destPropTypeName();
                if (!$srcPropIterable && $srcPropValueIsString && !empty($srcPropValue))
                {
                    $srcPropValue = json_decode($srcPropValue);
                    $srcPropIterable = true;
                    if(is_null($srcPropValue))
                    {
                        throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Property type mismatch [$destProp]! Expected [$destPropTypeName] got [string]. JSON decode attempt failed!");
                    }
                }
            }

            $destination->$destProp = $srcPropIterable ? autoMap($srcPropValue, $destInstance, $errorOnMismatch, $autoValidate) : $destInstance;
        }
        else
        {
            $destination->$destProp = $srcPropIterable && $destPropRef->isInitialized($destination) && $destination->$destProp ? autoMap($srcPropValue, $destination->$destProp, $errorOnMismatch, $autoValidate) : $srcPropValue;
        }
    }

    return $destination;
}