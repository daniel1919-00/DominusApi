<?php

namespace Dominus\System\Attributes;

use Attribute;

/**
 * Used to declare any method inside a model to be used for initialization.
 * This method will be called immediately after the Request object data has been mapped to the model properties.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class InitModel
{
    public function __construct(
        public string $modelInitMethod
    ) {}
}