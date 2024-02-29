<?php
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
        $appNamespace = env('APP_NAMESPACE', 'App\\');
        $baseDir = str_contains($className, $appNamespace) ? 'App' : 'Dominus';
        require PATH_ROOT . DIRECTORY_SEPARATOR . $baseDir . DIRECTORY_SEPARATOR .  str_replace('\\', DIRECTORY_SEPARATOR, str_replace([$appNamespace, 'Dominus\\'], '', ltrim($className, '\\'))) . '.php';
    });
}

require 'System' . DIRECTORY_SEPARATOR . 'Functions.php';

Router::_init(APP_ENV_CLI ? ($argv[1] ?? '') : $_SERVER['REQUEST_URI']);
require PATH_ROOT . DIRECTORY_SEPARATOR . 'startup.php';
AppConfiguration::loadDotEnv();
if(env('APP_ENV') === 'dev')
{
    require PATH_ROOT . DIRECTORY_SEPARATOR . 'Dominus' . DIRECTORY_SEPARATOR . 'System' . DIRECTORY_SEPARATOR . 'DebugHelpers' . DIRECTORY_SEPARATOR . 'include.php';
}