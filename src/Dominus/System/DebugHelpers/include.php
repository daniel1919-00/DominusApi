<?php

use Dominus\System\Models\LogType;

const PATH_DEBUG_HELPERS = PATH_ROOT . DIRECTORY_SEPARATOR . 'Dominus' . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'DebugHelpers';

require PATH_DEBUG_HELPERS . DIRECTORY_SEPARATOR . 'Functions.php';

if(env('APP_HANDLE_ERRORS') === '1')
{
    set_error_handler(static function (int $errorNumber, string $errorMsg, string $errorFile, int $errorLine = 0)
    {
        _log("[$errorFile]: $errorLine -> $errorMsg", match ($errorNumber)
        {
            E_ERROR, E_USER_ERROR => LogType::ERROR,
            E_WARNING, E_USER_WARNING => LogType::WARNING,
            default => LogType::INFO,
        });

        return true;
    }, E_ALL);

    register_shutdown_function(static function ()
    {
        $error = error_get_last();
        if ($error && ($error["type"] == E_ERROR || $error["type"] == E_COMPILE_ERROR))
        {
            _log($error["file"] . ': ' . $error["line"] . ' -> ' . $error['message'],LogType::ERROR);
        }
    });
}
