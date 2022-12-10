<?php
/**
 * @noinspection PhpIncludeInspection
 */

use Dominus\Services\Http\Models\HttpStatus;
use Dominus\System\Models\LogType;
use Dominus\System\Router;

define('ENV_CLI', http_response_code() === false);

require 'paths.php';
require 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require 'System' . DIRECTORY_SEPARATOR . 'Functions.php';

try
{
    loadDotEnvFile(PATH_ROOT . DIRECTORY_SEPARATOR . '.env');
}
catch (Exception $e)
{
    http_response_code(HttpStatus::INTERNAL_SERVER_ERROR->value);
    _log("Failed to load env file!", LogType::ERROR);
    exit;
}

if(env('APP_ENV') === 'dev')
{
    require PATH_ROOT . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'DebugHelpers' . DIRECTORY_SEPARATOR . 'include.php';
}
Router::init(ENV_CLI ? ($argv[1] ?? '') : $_SERVER['REQUEST_URI']);