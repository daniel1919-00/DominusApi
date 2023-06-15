<?php
namespace Dominus\System;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use Dominus\Services\Http\Models\HttpStatus;
use Dominus\System\Attributes\Middleware as MiddlewareAttribute;
use Dominus\System\Exceptions\RequestRejectedByMiddlewareException;

abstract class Middleware
{
    /**
     * @var MiddlewareResolution|null
     */
    private static mixed $lastMiddlewareData = null;

    /**
     * @throws RequestRejectedByMiddlewareException
     */
    public static function processMiddleware(ReflectionClass | ReflectionMethod $ref, Request $request): void
    {
        if($middleWareAttributes = $ref->getAttributes(MiddlewareAttribute::class))
        {
            foreach($middleWareAttributes as $middlewareAttribute)
            {
                $middlewareArguments = $middlewareAttribute->getArguments();
                self::executeMiddleware($middlewareArguments[0], $middlewareArguments[1] ?? [], $request);
            }
        }
    }

    /**
     * @throws RequestRejectedByMiddlewareException
     */
    public static function executeMiddleware(string $middlewareClass, array $middlewareArguments, Request $request): void
    {
        $middlewareClassConstructorArgs = [];

        try
        {
            $middlewareReflection = new ReflectionClass($middlewareClass);
            if($middlewareConstructor = $middlewareReflection->getConstructor())
            {
                $middlewareClassConstructorArgs = Injector::getDependencies($middlewareConstructor, new Request(parameters: $middlewareArguments));
            }
        }
        catch (Exception $e)
        {
            throw new RequestRejectedByMiddlewareException(
                new MiddlewareResolution(
                    rejected: true,
                    data: 'Failed to process ['.$middlewareClass.'] middleware! Reflection error:' . $e->getMessage(),
                    httpStatus: HttpStatus::INTERNAL_SERVER_ERROR
                )
            );
        }

        /**
         * @var Middleware $middleware
         */
        $middleware = new $middlewareClass(...$middlewareClassConstructorArgs);

        $middlewareResolution = $middleware->handle($request, self::$lastMiddlewareData?->data);
        if($middlewareResolution->rejected)
        {
            throw new RequestRejectedByMiddlewareException($middlewareResolution);
        }

        self::$lastMiddlewareData = $middlewareResolution;
    }

    /**
     * Handle the current request.
     *
     * @param Request $request
     * @param mixed $prevMiddlewareRes The data from the middleware that has run before this one.
     * The value will be NULL if there is no data or this is the first middleware to run.
     *
     * @return MiddlewareResolution
     */
    abstract public function handle(Request $request, mixed $prevMiddlewareRes): MiddlewareResolution;

    /**
     * @param mixed $data Data to be passed along the resolution to the next middleware
     *
     * @return MiddlewareResolution
     */
    protected function next(mixed $data = null): MiddlewareResolution
    {
        return new MiddlewareResolution(
            rejected: false,
            data: $data
        );
    }

    /**
     * Request is rejected.
     * No further middleware will run after this.
     *
     * @param string $reason
     * @param HttpStatus $httpStatusCode
     *
     * @return MiddlewareResolution
     */
    protected function reject(string $reason = '', HttpStatus $httpStatusCode = HttpStatus::BAD_REQUEST): MiddlewareResolution
    {
        return new MiddlewareResolution(
            rejected: true,
            data: $reason,
            httpStatus: $httpStatusCode,
        );
    }
}