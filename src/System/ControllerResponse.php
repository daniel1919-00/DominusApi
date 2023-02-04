<?php

namespace Dominus\System;

use Dominus\Services\Http\Models\HttpStatus;

class ControllerResponse
{
    public function __construct(
        public HttpStatus $statusCode,
        public mixed $data = null
    )
    {
    }
}