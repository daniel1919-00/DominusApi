<?php
namespace Dominus\System\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware 
{
    /**
     * @param string $middlewareClasses Middleware class name e.g. MyMiddleware::class
     * @param array $constructorArguments Associative array of arguments to be passed to the middleware constructor.
     * The keys must have the same name as the arguments expected in the constructors (excluding any Injectable classes).
     */
    public function __construct(
        public string $middlewareClasses,
        public array $constructorArguments = []
    ) {}
}