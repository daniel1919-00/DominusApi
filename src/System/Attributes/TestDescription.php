<?php

namespace Dominus\System\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_CLASS)]
class TestDescription
{
    public function __construct(
        public string $name
    )
    {
    }
}