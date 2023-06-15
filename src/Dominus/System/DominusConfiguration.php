<?php

namespace Dominus\System;

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
        if(env('APP_LOG_TO_FILE') === '1')
        {
            try
            {
                ob_start();
                debug_print_backtrace();
                $backtrace = ob_get_contents();
                ob_end_clean();

                $logFile = new SplFileObject((env('APP_LOG_FILE_LOCATION') ?: PATH_LOGS) . DIRECTORY_SEPARATOR . str_replace('{date}', date('Y-m-d'), env('APP_LOG_FILE_NAME_PATTERN')) . '.csv', 'a');
                $logFile->fputcsv([
                    date('H:i:s'),
                    $type->name,
                    $message,
                    $backtrace
                ]);
            }
            catch (Exception $e)
            {
                file_put_contents(PATH_LOGS . DIRECTORY_SEPARATOR . 'fatal-error-' . date('Y-m-d Hi'), 'Failed to write log file: ' . $e->getMessage(). PHP_EOL . PHP_EOL . ' Initial error message: ' . $message);
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
}