<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Http;

use stdClass;

class HttpResponse
{
    public function __construct(
        private readonly bool $error,
        private readonly int    $errorCode,
        private readonly string $errorMessage,
        private readonly int    $statusCode,
        private readonly mixed  $response
    )
    {
    }

    public function hasError(): bool
    {
        return $this->error;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRaw(): mixed
    {
        return $this->response;
    }

    /**
     * @param bool $convertToArray When TRUE, returned objects will be converted into associative arrays
     * @return array|stdClass
     */
    public function getJson(bool $convertToArray = true): array|stdClass
    {
        if($this->response)
        {
            return json_decode($this->response, $convertToArray);
        }
        else
        {
            return $convertToArray ? [] : new stdClass();
        }
    }
}