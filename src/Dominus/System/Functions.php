<?php

use Dominus\System\Attributes\Optional;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Models\LogType;

/**
 * @throws Exception
 */
function loadDotEnvFile(string $path): void
{
    if(!is_file($path))
    {
        throw new Exception("Missing .env file in: $path");
    }

    $envFile = fopen($path, 'r');
    if(!$envFile)
    {
        throw new Exception("Failed to parse .env file from: $path");
    }

    $captureName = true;
    $captureEverythingUntil = '';
    $skipUntilNewLine = false;
    $nextCharEscaped = false;

    $name = '';
    $value = '';
    while (false !== ($char = fgetc($envFile)))
    {
        if($skipUntilNewLine)
        {
            if($char === "\n")
            {
                $skipUntilNewLine = false;
            }
            continue;
        }

        if($captureEverythingUntil !== '')
        {
            if($nextCharEscaped)
            {
                $nextCharEscaped = false;
                $value .= $char;
            }
            else if($char === "\\")
            {
                $nextCharEscaped = true;
            }
            else if($captureEverythingUntil === $char)
            {
                $captureEverythingUntil = '';
            }
            else
            {
                $value .= $char;
            }
            continue;
        }

        switch($char)
        {
            case "\r":
            case ' ':
                break;

            case '"':
            case "'":
                $captureEverythingUntil = $char;
                break;

            case '#':
                if($name)
                {
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
                $skipUntilNewLine = true;
                $name = '';
                $value = '';
                $captureName = true;
                break;

            case '=':
                $captureName = false;
                break;

            case "\n":
                if($name)
                {
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
                $captureName = true;
                $name = '';
                $value = '';
                break;

            default:
                if($captureName)
                {
                    $name .= $char;
                }
                else
                {
                    $value .= $char;
                }
        }
    }

    fclose($envFile);

    if($name)
    {
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

/**
 * Adds a new log entry to the log file stored in the Logs directory
 */
function _log(string $message, LogType $type): void
{
    $logFile = new SplFileObject(PATH_LOGS . DIRECTORY_SEPARATOR . date('Y-m-d').'.csv', 'a');
    $logFile->fputcsv([
        date('H:i:s'),
        $type->name,
        $message,
        json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))
    ]);

    if(APP_ENV_DEV && APP_DISPLAY_LOGS && in_array($type->name, APP_DISPLAY_LOG_TYPES))
    {
        echo $message;
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
 * @param array | object $source
 * @param array | object | null $destination
 * @param bool $errorOnMismatch Will throw AutoMapPropertyMismatch if the source and destination does not have matching properties.
 * This parameter is ignored if the destination is not an object!
 *
 * @return array|object
 * @throws AutoMapPropertyMismatchException
 * @throws ReflectionException
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
        $srcPropExists = isset($source[$destProp]);
        $destPropAllowsNull = !$destPropType || $destPropType->allowsNull();

        if(!$srcPropExists)
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

        // assign data from source directly if the destination type is not known
        if(!$destPropType)
        {
            $destination->$destProp = $source[$destProp];
            continue;
        }

        $srcPropIsArray = $srcPropExists && is_array($source[$destProp]);
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
                    $srcDataType = gettype($source[$destProp]);
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
            $destination->$destProp = $srcPropIsArray ? autoMap($source[$destProp], new $destPropTypeName(), $errorOnMismatch) : new $destPropTypeName();
        }
        else
        {
            $destination->$destProp = $srcPropIsArray && $destPropRef->isInitialized($destination) && $destination->$destProp ? autoMap($source[$destProp], $destination->$destProp, $errorOnMismatch) : $source[$destProp];
        }
    }

    return $destination;
}