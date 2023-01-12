<?php
namespace Dominus\System;

use function lcfirst;
use function str_replace;
use function ucwords;

final class Router
{
    private static ?string $requestedModule = null;
    private static ?string $requestedController = null;
    private static ?string $requestedControllerMethod = null;
    
    public static function init(string $requestUri): void
    {
        $uri = strtok(filter_var(trim($requestUri, '/'), FILTER_SANITIZE_URL), '?');
        if(empty($uri))
        {
           return; 
        }

        $uriComponents = explode('/', $uri, 3);
        $moduleName = self::toCamelCase($uriComponents[0]);

        self::$requestedModule = $moduleName;
        self::$requestedController = (isset($uriComponents[1]) ? self::toCamelCase($uriComponents[1]) : $moduleName) . 'Controller';
        self::$requestedControllerMethod = isset($uriComponents[2]) ? lcfirst(self::toCamelCase($uriComponents[2])) : null;
    }

    public static function getRequest(): Request
    {
        return new Request(
            requestedController: self::$requestedController ?? '',
            requestedControllerMethod: self::$requestedControllerMethod ?? ''
        );
    }

    public static function getRequestedModule(): ?string
    {
        return self::$requestedModule;
    }

    public static function getRequestedController(): ?string
    {
        return self::$requestedController;
    }

    public static function getRequestedControllerMethod(): ?string
    {
        return self::$requestedControllerMethod;
    }

    private static function toCamelCase(string $str): string
    {
        return str_replace('-', '', ucwords($str, '-'));
    }
}