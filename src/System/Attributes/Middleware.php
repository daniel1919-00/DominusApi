<?php
namespace Dominus\System\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Middleware 
{
    public function __construct(
        /**
         * Middleware class name
         */
        public string $middlewareClasses,

        /**
         * Arguments to be passed to the middleware class constructor
         */
        public array $constructorArguments = []
    ) {}
}