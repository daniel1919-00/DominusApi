<?php

use Dominus\System\Attributes\DataModel\Optional;
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
 * This parameter is ignored if the destination is not an object!
 *
 * @return array|object
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
    $destProperties = $destRef->getProperties();

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

    foreach($destProperties as $destPropRef)
    {
        $destProp = $destPropRef->getName();
        $destPropOptional = !empty($destPropRef->getAttributes(Optional::class));
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
                throw new AutoMapPropertyMismatchException("Missing source property: $destProp");
            }
        }

        $srcPropValue = $source[$destProp];

        // assign data from source directly if the destination type is not known
        if(!$destPropType)
        {
            $destination->$destProp = $srcPropValue;
            continue;
        }

        $srcPropIsArray = is_array($srcPropValue);
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
                        throw new AutoMapPropertyMismatchException("Property type mismatch: $destProp! Expected [$destPropTypeName] got [$srcDataType].");
                    }
                }

                $destPropTypeName = '';
            }
        }

        if($destPropTypeName !== '')
        {
            if($errorOnMismatch && !$destPropAllowsNull && !$srcPropIsArray)
            {
                throw new AutoMapPropertyMismatchException("Missing source property: $destProp");
            }

            if(
                is_a($destPropTypeName, DateTime::class, true)
                || is_a($destPropTypeName, DateTimeImmutable::class, true)
            )
            {
                try
                {
                    $destInstance = new $destPropTypeName($srcPropValue);
                }
                catch (Exception)
                {
                    throw new AutoMapPropertyInvalidValue('Failed to construct ' . $destPropTypeName . ' from value: ' . $srcPropValue);
                }
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