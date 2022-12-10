<?php
namespace Dominus\System\Attributes;

use Attribute;

/**
 * Specifies the entrypoint method of this controller
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Entrypoint 
{
    public function __construct(
        public string $controllerEntrypointMethod
    ) {}
}
