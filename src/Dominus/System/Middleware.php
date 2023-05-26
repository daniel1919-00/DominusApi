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
    public static ?MiddlewareResolution $lastMiddlewareResolution = null;

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
                $middlewareClassConstructorArgs = [];

                try {
                    $middlewareReflection = new ReflectionClass($middlewareClass);
                    if($middlewareConstructor = $middlewareReflection->getConstructor())
                    {
                        $middlewareClassConstructorArgs = Injector::getDependencies($middlewareConstructor, new Request(parameters: $middlewareArguments[1] ?? []));
                    }
                }
                catch (Exception $e)
                {
                    throw new RequestRejectedByMiddlewareException(
                        new MiddlewareResolution(
                            rejected: true,
                            responseMsg: 'Failed to process ['.$ref->getName().'] middleware! Reflection error:' . $e->getMessage(),
                            httpStatusCode: HttpStatus::INTERNAL_SERVER_ERROR
                        )
                    );
                }

                /**
                 * @var Middleware $middleware
                 */
                $middleware = new $middlewareClass(...$middlewareClassConstructorArgs);

                $middlewareResolution = $middleware->handle($request);
                if($middlewareResolution->isRejected())
                {
                    throw new RequestRejectedByMiddlewareException($middlewareResolution);
                }
                self::$lastMiddlewareResolution = $middlewareResolution;
            }
            self::$lastMiddlewareResolution = null;
        }
    }

    /**
     * This method will handle the current request.
     * 
     * @param Request $request
     * @return MiddlewareResolution
     */
    abstract public function handle(Request $request): MiddlewareResolution;

    /**
     * Retrieves the resolution from the middleware that has run before this one.
     * Returns null if this is the first middleware to run.
     *
     * @return MiddlewareResolution|null
     */
    protected function getLastMiddlewareResolution(): ?MiddlewareResolution
    {
        return Middleware::$lastMiddlewareResolution;
    }

    /**
     * @param mixed $data Data to be passed along the resolution to the next middleware
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
     * No further middleware will be run after this one.
     *
     * @param string $responseMsg
     * @param HttpStatus $httpStatusCode
     * @return MiddlewareResolution
     */
    protected function reject(string $responseMsg = '', HttpStatus $httpStatusCode = HttpStatus::BAD_REQUEST): MiddlewareResolution
    {
        return new MiddlewareResolution(
            rejected: true,
            responseMsg: $responseMsg,
            httpStatusCode: $httpStatusCode,
        );
    }
}