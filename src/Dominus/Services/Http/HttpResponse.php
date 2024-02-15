<?php
namespace Dominus\Services\Http;

use stdClass;

class HttpResponse
{
    public function __construct(
        private readonly bool   $error,
        private readonly int    $errorCode,
        private readonly string $errorMessage,
        private readonly int    $statusCode,
        private readonly string  $response
    ) {}

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error;
    }

    /**
     * @return int Error code returned by curl_errno
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return int Http status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string Response returned by curl_exec
     */
    public function getRaw(): string
    {
        return $this->response;
    }

    /**
     * @param bool $toArray When true, the returned objects will be converted into associative arrays
     * @return array|stdClass
     */
    public function getJson(bool $toArray = false): array|stdClass
    {
        if($this->response)
        {
            return json_decode($this->response, $toArray);
        }
        else
        {
            return $toArray ? [] : new stdClass();
        }
    }
}