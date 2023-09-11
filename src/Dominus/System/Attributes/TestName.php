<?php

namespace Dominus\System\Attributes;

use Attribute;

/**
 * Attribute used by the test framework to describe:
 * * The suite name if placed on the class
 * * The test case name if placed on the method
 */
#[Attribute(Attribute::TARGET_METHOD|Attribute::TARGET_CLASS)]
class TestName
{
    public function __construct(
        public string $name
    )
    {
    }
}