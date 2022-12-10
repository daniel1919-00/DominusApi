<?php

namespace Dominus\System\Exceptions;

use Exception;
use Dominus\System\MiddlewareResolution;

class RequestRejectedByMiddlewareException extends Exception 
{
    public function __construct(
        private readonly MiddlewareResolution $middlewareResolution
    )
    {
        parent::__construct();
    }

    public function getResolution(): MiddlewareResolution
    {
        return $this->middlewareResolution;
    }
}