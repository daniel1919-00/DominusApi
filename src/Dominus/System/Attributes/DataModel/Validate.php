<?php

namespace Dominus\System\Attributes\DataModel;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validate
{
    /**
     * @param string $validationRules Validation rules to apply to this property.
     * Check documentation for the complete list of rules.
     * Example: rule1|rule2:rule2_arg1:rule2_arg2|rule3
     */
    public function __construct(
        public string $validationRules
    ) {}
}