<?php

namespace Dominus\System\Attributes;

use Attribute;

/**
 * Used by the test framework to fill the Request object
 * which is then used to simulate a user request.
 * For example:
 * <code>
 * #[TestRequestParameters([
 *     'request_field_1' => 'field value',
 *     'request_field_2' => 'field value',
 * ])]
 * </code>
 */
#[Attribute(Attribute::TARGET_METHOD)]
class TestRequestParameters
{
    public function __construct(
        public array $parameters
    )
    {
    }
}