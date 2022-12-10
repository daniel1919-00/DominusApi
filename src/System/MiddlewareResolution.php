<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpStatus;

class MiddlewareResolution
{
    public function __construct(
        private readonly bool   $rejected,
        private readonly string $responseMsg = '',
        private readonly ?HttpStatus $httpStatusCode = HttpStatus::BAD_REQUEST
    ) {}

    public function isRejected(): bool
    {
        return $this->rejected;
    }

    public function getHttpStatus(): ?HttpStatus
    {
        return $this->httpStatusCode;
    }

    public function getResponseMsg(): string
    {
        return $this->responseMsg;
    }
}