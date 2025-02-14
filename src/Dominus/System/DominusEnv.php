<?php

namespace Dominus\System;

use Exception;
use function dirname;
use function is_file;
use function substr;
use const DIRECTORY_SEPARATOR;

class DominusEnv
{
    /**
     * @throws Exception
     */
    public static function load(string $path): void
    {
        if(!is_file($path))
        {
            throw new Exception('Failed to open env file for parsing from: ' . $path . '. No such file!');
        }

        $envFileDirectoryPath = dirname($path);
        $envFileContents = file_get_contents($path);
        $envFileContentsLen = strlen($envFileContents);

        $captureEnvVarName = true;
        $captureEverythingUntilChar = '';
        $skipUntilNewLine = false;
        $nextCharEscaped = false;
        $interpolatedSymbol = '';
        $interpolating = false;

        $envVarName = '';
        $envVarValue = '';
        for($charIndex = 0; $charIndex < $envFileContentsLen; ++$charIndex)
        {
            $char = $envFileContents[$charIndex];

            if($skipUntilNewLine)
            {
                if($char === "\n")
                {
                    $skipUntilNewLine = false;
                }
                continue;
            }

            if($interpolating)
            {
                if($char === "}")
                {
                    $char = $_SERVER[$interpolatedSymbol] ?? '';
                    $interpolatedSymbol = '';
                    $interpolating = false;
                }
                else
                {
                    $interpolatedSymbol .= $char;
                    continue;
                }
            }

            if($captureEverythingUntilChar !== '')
            {
                if($nextCharEscaped)
                {
                    $nextCharEscaped = false;
                    $envVarValue .= $char;
                }
                else if($char === "\\")
                {
                    $nextCharEscaped = true;
                }
                else if($captureEverythingUntilChar === $char)
                {
                    $captureEverythingUntilChar = '';
                }
                else if($char === "$")
                {
                    $nextCharIndex = $charIndex + 1;
                    if($nextCharIndex < $envFileContentsLen && $envFileContents[$nextCharIndex] === '{')
                    {
                        ++$charIndex;
                        $interpolating = true;
                    }
                    else
                    {
                        $envVarValue .= $char;
                    }
                }
                else
                {
                    $envVarValue .= $char;
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
                    $captureEverythingUntilChar = $char;
                    break;

                case '$':
                    $nextCharIndex = $charIndex + 1;
                    if(!$captureEnvVarName && $nextCharIndex < $envFileContentsLen && $envFileContents[$nextCharIndex] === '{')
                    {
                        ++$charIndex;
                        $interpolating = true;
                    }
                    else if($captureEnvVarName)
                    {
                        $envVarName .= $char;
                    }
                    else
                    {
                        $envVarValue .= $char;
                    }
                    break;

                case '#':
                    if($envVarName)
                    {
                        self::storeEnvironmentVariable($envVarName, $envVarValue, $envFileDirectoryPath);
                    }
                    $skipUntilNewLine = true;
                    $envVarName = '';
                    $envVarValue = '';
                    $captureEnvVarName = true;
                    break;

                case '=':
                    $captureEnvVarName = false;
                    break;

                case "\n":
                    if($envVarName)
                    {
                        self::storeEnvironmentVariable($envVarName, $envVarValue, $envFileDirectoryPath);
                    }
                    $captureEnvVarName = true;
                    $envVarName = '';
                    $envVarValue = '';
                    break;

                default:
                    if($captureEnvVarName)
                    {
                        $envVarName .= $char;
                    }
                    else
                    {
                        $envVarValue .= $char;
                    }
            }
        }

        if($envVarName)
        {
            self::storeEnvironmentVariable($envVarName, $envVarValue, $envFileDirectoryPath);
        }
    }

    /**
     * @throws Exception
     */
    private static function storeEnvironmentVariable(string $varName, $value, string $envFileDirPath): void
    {
        if(str_starts_with($varName, '@import'))
        {
            self::load($envFileDirPath . DIRECTORY_SEPARATOR . substr($varName, 7));
        }
        else
        {
            $_SERVER[$varName] = $value;
            $_ENV[$varName] = $value;
        }
    }
}