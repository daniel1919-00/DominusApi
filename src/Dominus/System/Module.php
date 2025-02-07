<?php
namespace Dominus\System;

use AppConfiguration;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Dominus\System\Attributes\Entrypoint;
use Dominus\System\Attributes\RequestMethod;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Exceptions\ControllerMethodNotFoundException;
use Dominus\System\Exceptions\RequestMethodNotAllowedException;
use Dominus\System\Exceptions\ControllerNotFoundException;
use Dominus\System\Exceptions\RequestRejectedByMiddlewareException;
use function class_exists;
use function strtoupper;

final class Module 
{
    /**
     * Attempts to load a module if exists. NULL is returned if it is not found!
     * @param string $moduleName The module name to load
     * @return Module
     */
    public static function load(string $moduleName): Module
    {
        return new Module($moduleName);
    }

    public function __construct(
        private $moduleName
    ) {}

    /**
     * @param string $controllerName
     * @param Request|null $request Used only if this controller's constructor has dependencies outside services
     * @param bool $ignoreEnvChecks Allows running CliControllers in non-cli environment
     * @return Controller
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     * @throws ControllerNotFoundException
     * @throws DependenciesNotMetException
     * @throws RequestRejectedByMiddlewareException
     * @throws Exception
     */
    public function getController(string $controllerName, ?Request $request = null, bool $ignoreEnvChecks = false): Controller
    {
        $controllerClass = '\\'.env('APP_NAMESPACE', 'App\\')."Modules\\$this->moduleName\\Controllers\\$controllerName";
        if(!class_exists($controllerClass))
        {
            throw new ControllerNotFoundException("Controller not found: $controllerClass");
        }

        $controllerReflection = new ReflectionClass($controllerClass);

        if(!$ignoreEnvChecks && !APP_ENV_CLI && $controllerReflection->isSubclassOf(CliController::class))
        {
            throw new Exception('This controller can only be instantiated from a CLI environment!');
        }

        $entryPoint = $controllerReflection->getAttributes(Entrypoint::class);
        $controllerEntrypoint = null;
        if(isset($entryPoint[0]))
        {
            $entrypointMethod = $entryPoint[0]->getArguments();
            if(isset($entrypointMethod[0]))
            {
                $controllerEntrypoint = $entrypointMethod[0];
            }
        }

        if(!$request)
        {
            $request = Router::getRequest();
        }

        $request->setControllerName($controllerName);
        if(!$request->getControllerMethodName())
        {
            $request->setControllerMethodName(Router::getRequestedControllerMethod() ?: ($controllerEntrypoint ?: ''));
        }

        Middleware::processMiddleware($controllerReflection, $request);

        $controllerConstructorRef = $controllerReflection->getConstructor();
        
        // Check if dependency injection is required for the class constructor
        $controller = $controllerConstructorRef ? new ($controllerClass)(...Injector::getDependencies($controllerConstructorRef, $request)) : new ($controllerClass)();

        if(!($controller instanceof Controller))
        {
            throw new Exception('Route controller not an instance of Controller class!');
        }

        return $controller;
    }

    /**
     * @param Request $request
     * @param bool $ignoreEnvChecks Allows running CliControllers in non-cli environment
     * @return mixed
     * @throws AutoMapPropertyInvalidValue
     * @throws AutoMapPropertyMismatchException
     * @throws ControllerMethodNotFoundException
     * @throws ControllerNotFoundException
     * @throws DependenciesNotMetException
     * @throws RequestMethodNotAllowedException
     * @throws RequestRejectedByMiddlewareException
     */
    public function run(Request $request, bool $ignoreEnvChecks = false): mixed
    {
        if(AppConfiguration::$globalMiddleware)
        {
            foreach (AppConfiguration::$globalMiddleware as $globalMiddleware)
            {
                if(is_array($globalMiddleware))
                {
                    Middleware::executeMiddleware($globalMiddleware[0], $globalMiddleware[1], $request);
                }
                else
                {
                    Middleware::executeMiddleware($globalMiddleware, [], $request);
                }
            }
        }

        $controller = $this->getController($request->getControllerName(), $request, $ignoreEnvChecks);
        $controllerMethod = $request->getControllerMethodName();

        if(!$controllerMethod)
        {
            throw new ControllerMethodNotFoundException("Controller method not provided!");
        }

        try 
        {
            $methodRef = new ReflectionMethod($controller, $controllerMethod);
        }
        catch(Exception $e)
        {
            throw new ControllerMethodNotFoundException("Failed to instantiate reflection for method $controllerMethod! Error: " . $e->getMessage());
        }

        $checkRequestMethod = $methodRef->getAttributes(RequestMethod::class);
        if($checkRequestMethod)
        {
            $requestMethod = $checkRequestMethod[0]->getArguments()[0] ?? null;
            if($requestMethod && strtoupper($requestMethod) !== $request->getMethod()->name)
            {
                throw new RequestMethodNotAllowedException("Request type mismatch expected: " . $requestMethod . ', got: ' . $request->getMethod()->name);
            }
        }

        Middleware::processMiddleware($methodRef, $request);

        return $controller->$controllerMethod(...Injector::getDependencies($methodRef, $request));
    }
}