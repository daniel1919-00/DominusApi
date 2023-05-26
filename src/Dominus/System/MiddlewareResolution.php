<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpStatus;

class MiddlewareResolution
{
    public function __construct(
        private readonly bool   $rejected,
        private readonly mixed $data = null,
        private readonly string $responseMsg = '',
        private readonly ?HttpStatus $httpStatusCode = HttpStatus::BAD_REQUEST
    ) {}

    /**
     * The data set by the resolved middleware
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }

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