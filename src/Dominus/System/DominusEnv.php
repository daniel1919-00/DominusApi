<?php

namespace Dominus\System;

use Exception;
use function fclose;
use function fgetc;
use function fopen;
use function is_file;

class DominusEnv
{
    /**
     * @throws Exception
     */
    public static function load(string $path): void
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
}