<?php
use Dominus\Services\Http\Models\HttpStatus;
use Dominus\System\DominusEnv;
use Dominus\System\Router;

const APP_ENV_CLI = PHP_SAPI === 'cli';

require 'paths.php';

if(is_file(PATH_COMPOSER_AUTOLOADER))
{
    require PATH_COMPOSER_AUTOLOADER;
}
else
{
    // No composer means we need to autoload classes ourselves
    spl_autoload_register(static function($className)
    {
        $appNamespace = env('APP_NAMESPACE');
        $baseDir = str_contains($className, $appNamespace) ? 'App' : 'Dominus';
        require PATH_ROOT . DIRECTORY_SEPARATOR . $baseDir . DIRECTORY_SEPARATOR .  str_replace('\\', DIRECTORY_SEPARATOR, str_replace([$appNamespace, 'Dominus\\'], '', ltrim($className, '\\'))) . '.php';
    });
}

try
{
    DominusEnv::load(PATH_ROOT . DIRECTORY_SEPARATOR . '.env');
}
catch (Exception $e)
{
    http_response_code(HttpStatus::INTERNAL_SERVER_ERROR->value);
    file_put_contents(PATH_LOGS . DIRECTORY_SEPARATOR . 'env-load-error.txt', $e->getMessage());
    exit;
}

require 'System' . DIRECTORY_SEPARATOR . 'Functions.php';

if(env('APP_ENV') === 'dev')
{
    require PATH_ROOT . DIRECTORY_SEPARATOR . 'Dominus' . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'DebugHelpers' . DIRECTORY_SEPARATOR . 'include.php';
}

Router::_init(APP_ENV_CLI ? ($argv[1] ?? '') : $_SERVER['REQUEST_URI']);