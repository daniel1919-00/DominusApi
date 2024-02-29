<?php

use Dominus\Services\Validator\Validator;
use Dominus\System\Attributes\DataModel\Optional;
use Dominus\System\Attributes\DataModel\TrimString;
use Dominus\System\Attributes\DataModel\Validate;
use Dominus\System\DominusConfiguration;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Models\LogType;

/**
 * Adds a new log entry to the log file stored in the Logs directory
 */
function _log(string $message, LogType $type): void
{
    if(class_exists('AppConfiguration'))
    {
        AppConfiguration::log($message, $type);
    }
    else
    {
        DominusConfiguration::log($message, $type);
    }
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
 * @param array|object|null $source
 * @param array | object | null $destination
 * @param bool $errorOnMismatch Will throw AutoMapPropertyMismatch if the source and destination does not have matching properties.
 * @param bool $autoValidate Validate properties that have the #[Validate()] attribute
 *
 * @return array|object|null
 *
 * @throws AutoMapPropertyInvalidValue
 * @throws AutoMapPropertyMismatchException
 * @throws ReflectionException
 */
function autoMap(array | object | null $source, array | object | null $destination, bool $errorOnMismatch = true, bool $autoValidate = true): array | object | null
{
    if(empty($destination))
    {
        return $source;
    }

    $destinationIsObject = is_object($destination);

    if(empty($source))
    {
        if ($errorOnMismatch)
        {
            throw new AutoMapPropertyMismatchException('Error mapping model [' .($destinationIsObject ? $destination::class : 'N/A') . ']: Empty source!');
        }
        return $destination;
    }

    $sourceIsObject = is_object($source);
    if(!$destinationIsObject)
    {
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

    $validator = $autoValidate ? new Validator() : null;

    foreach($destProperties as $destPropRef)
    {
        $destProp = $destPropRef->getName();
        $destPropType = $destPropRef->getType();
        $destPropAllowsNull = !$destPropType || $destPropType->allowsNull();
        $srcPropValue = $sourceIsObject ? ($source->$destProp ?? null) : ($source[$destProp] ?? null);

        if(is_null($srcPropValue))
        {
            if($destPropAllowsNull)
            {
                $destination->$destProp = null;
                continue;
            }
            else if($destPropRef->getAttributes(Optional::class))
            {
                continue;
            }
            else if($errorOnMismatch)
            {
                throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Source property [$destProp] has null value!");
            }
        }

        $srcDataType = gettype($srcPropValue);

        if($srcPropValue && $srcDataType === 'string' && $destPropRef->getAttributes(TrimString::class))
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

        $destDataType = '';
        if(!($destPropType instanceof ReflectionNamedType))
        {
            $unionTypes = $destPropType->getTypes();
            $firstIterablePropTypeName = '';
            foreach($unionTypes as $unionType)
            {
                $propTypeName = $unionType->getName();
                if(!$unionType->isBuiltin())
                {
                    $destDataType = $propTypeName;
                    break;
                }
                else if ($firstIterablePropTypeName === '' && $propTypeName === 'array')
                {
                    $firstIterablePropTypeName = $propTypeName;
                }
            }

            if($destDataType === '' && $firstIterablePropTypeName !== '')
            {
                $destDataType = $firstIterablePropTypeName;
            }
        }
        else
        {
            $destDataType = $destPropType->getName();
            if($destPropType->isBuiltin() && $destDataType !== 'array')
            {
                $destDataType = '';
            }
        }

        if($destDataType !== '')
        {
            if(is_a($destDataType, DateTimeImmutable::class, true) || is_a($destDataType, DateTime::class, true))
            {
                try
                {
                    $destInstance = new $destDataType($srcPropValue);
                    $srcPropIterable = false;
                }
                catch (Exception)
                {
                    throw new AutoMapPropertyInvalidValue('Error mapping model ['.$destination::class.']: Failed to construct [' . $destDataType . '] from value [' . $srcPropValue . ']');
                }
            }
            else if(enum_exists($destDataType))
            {
                /**
                 * @var $destDataType BackedEnum
                 */
                $destInstance = $destDataType::tryFrom($srcPropValue);
                $srcPropIterable = false;

                if(is_null($destInstance) && !$destPropAllowsNull)
                {
                    throw new AutoMapPropertyInvalidValue('Error mapping model ['.$destination::class.']: Failed to construct enum [' . $destDataType . '] from value [' . $srcPropValue . ']');
                }
            }
            else
            {
                if ($srcPropValue !== '' && !($srcDataType === 'array' || $srcDataType === 'object'))
                {
                    $srcPropValue = json_decode($srcPropValue, false);
                    $srcDataType = gettype($srcPropValue);
                    if(is_null($srcPropValue))
                    {
                        throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Property type mismatch [$destProp]! Expected [$destDataType] got [string]. JSON decode attempt failed!");
                    }
                }

                $srcPropIterable = true;
                if($destDataType === 'array')
                {
                    if($errorOnMismatch && $destDataType !== $srcDataType)
                    {
                        throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Property type mismatch [$destProp]! Expected [$destDataType] got [$srcDataType].");
                    }
                    $destInstance = $srcPropValue;
                }
                else
                {
                    if($errorOnMismatch && $srcDataType !== 'object')
                    {
                        throw new AutoMapPropertyMismatchException("Error mapping model [".$destination::class."]: Property type mismatch [$destProp]! Expected [$destDataType] got [$srcDataType].");
                    }

                    if($destDataType === 'stdClass')
                    {
                        $destInstance = $srcPropValue;
                        $srcPropIterable = false;
                    }
                    else
                    {
                        $destInstance = new $destDataType();
                    }
                }
            }

            $destination->$destProp = $srcPropIterable ? autoMap($srcPropValue, $destInstance, $errorOnMismatch, $autoValidate) : $destInstance;
        }
        else
        {
            $destination->$destProp = $srcPropValue;
        }
    }

    return $destination;
}