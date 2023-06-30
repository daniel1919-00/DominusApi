<?php

namespace Dominus\System;

use Dominus\System\Attributes\DataModel\InitModel;
use Dominus\System\Exceptions\AutoMapPropertyInvalidValue;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Interfaces\Injectable\Factory;
use Dominus\System\Interfaces\Injectable\Injectable;
use Dominus\System\Interfaces\Injectable\Singleton;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use function autoMap;
use function class_implements;
use function is_null;

class Injector
{
    private static array $sharedInstances = [];

    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
     * @throws AutoMapPropertyInvalidValue
     * @throws Exception
     */
    public static function getDependencies(ReflectionMethod $methodReflection, Request $request): array
    {
        $dependencies = [];

        $methodParameters = $methodReflection->getParameters();
        if(!$methodParameters)
        {
            return $dependencies;
        }

        $currentClass = $methodReflection->getDeclaringClass()->getName();
        $requestParams = $request->getAll();

        foreach($methodParameters as $param)
        {
            $paramType = $param->getType();
            $dependency = null;

            if(!$paramType)
            {
                $dependency = $request->get($param->getName());
            }
            else if (!($paramType instanceof ReflectionNamedType) || $paramType->isBuiltin())
            {
                $requestValue = $request->get($param->getName());

                if(is_null($requestValue) && !$paramType->allowsNull())
                {
                    throw new DependenciesNotMetException("Injection failed! Attempted to pass null argument (missing from request?) when method does not allow it! Required argument -> " . $param->getName().': '.$param->getType());
                }

                $dependency = $requestValue;
            }
            else
            {
                $paramTypeName = $paramType->getName();
                if($paramTypeName === Request::class)
                {
                    $dependency = $request;
                }
                else if(($interfaces = class_implements($paramTypeName)) && isset($interfaces[Injectable::class]))
                {
                    if($currentClass === $paramTypeName)
                    {
                        throw new Exception("Dependency injection error: Circular dependency in $paramTypeName");
                    }

                    if(isset(self::$sharedInstances[$paramTypeName]))
                    {
                        $dependency = self::$sharedInstances[$paramTypeName];
                    }
                    else
                    {
                        if(isset($interfaces[Factory::class]))
                        {
                            /**
                             * @var Factory $paramTypeName
                             */
                            $dependency = $paramTypeName::_getInjectionInstance();
                        }
                        else
                        {
                            $dependencyClassRef = new ReflectionClass($paramTypeName);
                            $dependencyClassConstructorRef = $dependencyClassRef->getConstructor();
                            $dependency = $dependencyClassConstructorRef ? new $paramTypeName(...self::getDependencies($dependencyClassConstructorRef, $request)) : new $paramTypeName();
                        }

                        if(isset($interfaces[Singleton::class]))
                        {
                            self::$sharedInstances[$paramTypeName] = $dependency;
                        }
                    }
                }
                else
                {
                    $dependency = autoMap($requestParams, new $paramTypeName());
                    $dependencyRef = new ReflectionClass($dependency);
                    $dependencyAttrs = $dependencyRef->getAttributes(InitModel::class);
                    if(isset($dependencyAttrs[0]))
                    {
                        $dependencyInitMethod = $dependencyAttrs[0]->getArguments();
                        if(isset($dependencyInitMethod[0]))
                        {
                            $dependency->{$dependencyInitMethod[0]}();
                        }
                    }
                }
            }

            $dependencies[] = $dependency;
        }

        return $dependencies;
    }
}