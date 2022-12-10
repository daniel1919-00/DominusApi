<?php

namespace Dominus\System\Attributes;
use Attribute;

/**
 * Limits the access to this method to a specific request method
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequestMethod
{
    public function __construct(
        public string $requestMethod
    ){}
}