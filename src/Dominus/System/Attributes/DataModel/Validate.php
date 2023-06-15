<?php

namespace Dominus\System\Attributes\DataModel;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(
        public array $validationRules
    ) {}
}