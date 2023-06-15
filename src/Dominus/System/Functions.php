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
 *
 * @return array|object
 *
 * @throws AutoMapPropertyMismatchException
 * @throws ReflectionException
 * @throws AutoMapPropertyInvalidValue
 */
function autoMap(array | object $source, array | object | null $destination, bool $errorOnMismatch = true): array | object
{
    if(is_null($destination))
    {
        return $source;
    }

    if(is_object($source))
    {
        $source = get_object_vars($source);
    }

    if(!is_object($destination))
    {
        if(!$source)
        {
            return $destination;
        }

        if(empty($destination))
        {
            return $source;
        }

        foreach ($destination as $destinationKey)
        {
            if(isset($source[$destinationKey]))
            {
                $destination[$destinationKey] = is_array($destination[$destinationKey]) ? autoMap($source[$destinationKey], $destination[$destinationKey], $errorOnMismatch) : $source[$destinationKey];
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
            throw new AutoMapPropertyMismatchException('Invalid source!');
        }
    }

    $validator = new Validator();

    foreach($destProperties as $destPropRef)
    {
        $destProp = $destPropRef->getName();
        $destPropOptional = (bool)$destPropRef->getAttributes(Optional::class);
        $destPropType = $destPropRef->getType();
        $destPropAllowsNull = !$destPropType || $destPropType->allowsNull();

        if(!isset($source[$destProp]))
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
                throw new AutoMapPropertyMismatchException("Missing source property [$destProp]");
            }
        }

        $srcPropValue = $source[$destProp];

        if($srcPropValue && is_string($srcPropValue) && $destPropRef->getAttributes(TrimString::class))
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
                        throw new AutoMapPropertyMismatchException("Property type mismatch [$destProp]! Expected [$destPropTypeName] got [$srcDataType].");
                    }
                }

                $destPropTypeName = '';
            }
        }

        $srcPropIsArray = is_array($srcPropValue);

        if($destPropTypeName !== '')
        {
            if($destPropTypeName === 'DateTime' || $destPropTypeName === 'DateTimeImmutable')
            {
                try
                {
                    $destInstance = new $destPropTypeName($srcPropValue);
                }
                catch (Exception)
                {
                    throw new AutoMapPropertyInvalidValue('Failed to construct [' . $destPropTypeName . '] from value [' . $srcPropValue . ']');
                }
            }
            else if(enum_exists($destPropTypeName))
            {
                /**
                 * @var $destPropTypeName BackedEnum
                 */
                $destInstance = $destPropTypeName::from($srcPropValue);
            }
            else
            {
                $destInstance = new $destPropTypeName();
            }

            $destination->$destProp = $srcPropIsArray ? autoMap($srcPropValue, $destInstance, $errorOnMismatch) : $destInstance;
        }
        else
        {
            // primitive type
            $destination->$destProp = $srcPropIsArray && $destPropRef->isInitialized($destination) && $destination->$destProp ? autoMap($srcPropValue, $destination->$destProp, $errorOnMismatch) : $srcPropValue;
        }
    }

    return $destination;
}