<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpStatus;

class MiddlewareResolution
{
    public function __construct(
        public readonly bool       $rejected,
        public readonly mixed      $data = null,
        public readonly HttpStatus $httpStatus = HttpStatus::BAD_REQUEST
    ) {}
}