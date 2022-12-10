<?php

namespace Dominus\System\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class TestRequestParameters
{
    public function __construct(
        public array $parameters
    )
    {
    }
}