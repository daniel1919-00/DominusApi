<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\System;

use ReflectionClass;
use ReflectionMethod;
use Dominus\Services\Http\Models\HttpStatus;
use Dominus\System\Attributes\Middleware as MiddlewareAttribute;
use Dominus\System\Exceptions\RequestRejectedByMiddlewareException;

abstract class Middleware
{
    /**
     * @throws RequestRejectedByMiddlewareException
     */
    public static function runMiddleware(ReflectionClass | ReflectionMethod $ref, Request $request): void
    {
        if($middleWareAttributes = $ref->getAttributes(MiddlewareAttribute::class))
        {
            foreach($middleWareAttributes as $middlewareAttribute)
            {
                $middlewareArguments = $middlewareAttribute->getArguments();
                $middlewareClass = $middlewareArguments[0];
                $middlewareClassConstructorArgs = $middlewareArguments[1] ?? [];

                /**
                 * @var Middleware $middleware
                 */
                $middleware = new $middlewareClass(...$middlewareClassConstructorArgs);
                $middlewareResolution = $middleware->handle($request);
                if($middlewareResolution->isRejected())
                {
                    throw new RequestRejectedByMiddlewareException($middlewareResolution);
                }
            }
        }
    }

    /**
     * This method will handle the current request.
     * 
     * @param Request $request
     * @return MiddlewareResolution
     */
    abstract public function handle(Request $request): MiddlewareResolution;

    protected function next(): MiddlewareResolution
    {
        return new MiddlewareResolution(
            false
        );
    }

    protected function reject(string $responseMsg = '', HttpStatus $httpStatusCode = HttpStatus::BAD_REQUEST): MiddlewareResolution
    {
        return new MiddlewareResolution(
            rejected: true,
            responseMsg: $responseMsg,
            httpStatusCode: $httpStatusCode,
        );
    }
}