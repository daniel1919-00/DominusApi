<?php

namespace Dominus\System;

use Dominus\System\Interfaces\MigrationsStorage;
use Dominus\System\Models\LogType;
use Exception;
use SplFileObject;
use function date;
use function debug_print_backtrace;
use function env;
use function explode;
use function file_put_contents;
use function in_array;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;
use function str_replace;
use function strtoupper;
use const DIRECTORY_SEPARATOR;
use const PATH_LOGS;
use const PATH_ROOT;
use const PHP_EOL;

abstract class DominusConfiguration
{
    public static array $globalMiddleware = [];

    public static abstract function init();

    /**
     * Global log function, you can override this to implement your own logging functionality
     * @param string $message
     * @param LogType $type
     * @return void
     */
    public static function log(string $message, LogType $type): void
    {
        if(env('APP_LOG_TO_FILE', '1') === '1')
        {
            try
            {
                ob_start();
                debug_print_backtrace();
                $backtrace = ob_get_contents();
                ob_end_clean();

                $logFile = new SplFileObject((env('APP_LOG_FILE_LOCATION') ?: PATH_LOGS) . DIRECTORY_SEPARATOR . str_replace('{date}', date('Y-m-d'), env('APP_LOG_FILE_NAME_PATTERN', 'dominus-log-{date}')) . '.csv', 'a');
                $logFile->fputcsv([
                    date('H:i:s'),
                    $type->name,
                    $message,
                    $backtrace
                ]);
            }
            catch (Exception $e)
            {
                file_put_contents(PATH_LOGS . DIRECTORY_SEPARATOR . 'fatal-error-' . date('Y-m-d_Hi').'.csv', 'Failed to write log file: ' . $e->getMessage(). PHP_EOL . PHP_EOL . ' Initial error message: ' . $message);
            }
        }

        if(env('APP_ENV') === 'dev' &&  env('APP_DISPLAY_LOGS') === '1' && in_array($type->name, explode(',', strtoupper(env('APP_DISPLAY_LOG_TYPES')))))
        {
            if(!APP_ENV_CLI)
            {
                echo '<pre>';
            }

            echo '['.date('H:i:s') . '] ' . $message . PHP_EOL;

            if(!APP_ENV_CLI)
            {
                echo '</pre>';
            }
        }
    }

    /**
     * To implement a custom storage for the applied migrations,
     * override this function and return a class that implements the MigrationsConfig interface
     * @return MigrationsStorage
     */
    public static function getMigrationsStorage(): MigrationsStorage
    {
        return new DefaultMigrationsStorage();
    }

    /**
     * Loads the core .env file
     * You can override this method to implement your own .env loader.
     * If you don't use .env files, override this with an empty body method.
     *
     * @return void
     */
    public static function loadDotEnv(): void
    {
        try
        {
            DominusEnv::load(PATH_ROOT . DIRECTORY_SEPARATOR . '.env');
        }
        catch (Exception $e)
        {
            self::log($e->getMessage(), LogType::ERROR);
        }
    }
}