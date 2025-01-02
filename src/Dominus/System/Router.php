<?php
namespace Dominus\System;

use function explode;
use function filter_var;
use function lcfirst;
use function str_replace;
use function trim;
use function ucwords;
use const FILTER_SANITIZE_URL;

final class Router
{
    private static ?Request $request = null;
    private static ?string $requestedModule = null;
    private static ?string $requestedController = null;
    private static ?string $requestedControllerMethod = null;

    public static function _init(string $requestUri): void
    {
        $uri = explode('?', filter_var(trim($requestUri, '/'), FILTER_SANITIZE_URL), 2);
        if(!$uri)
        {
            return;
        }

        $uriComponents = explode('/', $uri[0], 3);
        if(!isset($uriComponents[0]))
        {
            return;
        }

        $moduleName = self::toCamelCase($uriComponents[0]);

        self::$requestedModule = $moduleName;
        self::$requestedController = (isset($uriComponents[1]) ? self::toCamelCase($uriComponents[1]) : $moduleName) . 'Controller';
        self::$requestedControllerMethod = isset($uriComponents[2]) ? lcfirst(self::toCamelCase($uriComponents[2])) : null;
    }

    public static function getRequest(): Request
    {
        if(!self::$request)
        {
            self::$request = new Request(
                requestedController: self::$requestedController ?? '',
                requestedControllerMethod: self::$requestedControllerMethod ?? ''
            );
        }

        return self::$request;
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