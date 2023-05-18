<?php

namespace Dominus\System\Attributes;
use Attribute;

/**
 * Limits access to this method to a specific request method (GET, POST, etc.)
 */
#[Attribute(Attribute::TARGET_METHOD)]
class RequestMethod
{
    public function __construct(
        public string $requestMethod
    ){}
}