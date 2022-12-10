<?php

namespace Dominus\System;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use function autoMap;
use function is_null;
use function is_subclass_of;
use function method_exists;

class Injector
{
    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
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
                    throw new DependenciesNotMetException();
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
                else if(is_subclass_of($paramTypeName, Injectable::class))
                {
                    if($currentClass === $paramTypeName)
                    {
                        throw new Exception("Dependency injection error: Circular dependency in $paramTypeName");
                    }

                    if(method_exists($paramTypeName, '_getInjectionInstance'))
                    {
                        /** @noinspection PhpMethodParametersCountMismatchInspection */
                        $dependency = $paramTypeName::_getInjectionInstance(...self::getDependencies(new ReflectionMethod($paramTypeName, '_getInjectionInstance'), $request));
                    }
                    else
                    {
                        $dependencyClassRef = new ReflectionClass($paramTypeName);
                        $dependencyClassConstructorRef = $dependencyClassRef->getConstructor();
                        $dependency = $dependencyClassConstructorRef ? new $paramTypeName(...self::getDependencies($dependencyClassConstructorRef, $request)) : new $paramTypeName();
                    }
                }
                else
                {
                    $dependency = autoMap($request->getAll(), new $paramTypeName());
                }
            }

            $dependencies[] = $dependency;
        }

        return $dependencies;
    }
}