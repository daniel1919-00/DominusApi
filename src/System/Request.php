<?php
/**
 * @noinspection PhpUnused
 */

namespace Dominus\System;

use Dominus\Services\Http\Models\HttpDataType;
use Dominus\System\Models\RequestMethod;

final class Request
{
    private array $headers = [];

    public function __construct(
        private ?RequestMethod $method = null,
        private ?array $parameters = null,
        private string $requestedController = '',
        private string $requestedControllerMethod = ''
    ) 
    {
        if(!ENV_CLI)
        {
            if(!$method)
            {
                $this->method = match($_SERVER['REQUEST_METHOD']) {
                    RequestMethod::GET->name => RequestMethod::GET,
                    RequestMethod::POST->name => RequestMethod::POST,
                    RequestMethod::PUT->name => RequestMethod::PUT,
                    RequestMethod::DELETE->name => RequestMethod::DELETE,
                    RequestMethod::PATCH->name => RequestMethod::PATCH
                };
            }
            $this->fetchHeaders();
        }

        if(!$this->parameters)
        {
            $this->processParameters();
        }
    }

    /**
     * @param string $header required header i.e. X-REQUESTED-WITH
     */
    public function getHeader(string $header): ?string
    {
        return $this->headers[strtoupper($header)] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setControllerName(string $name): void
    {
        $this->requestedController = $name;
    }

    public function getControllerName(): string
    {
        return $this->requestedController;
    }

    public function setControllerMethodName(string $name): void
    {
        $this->requestedControllerMethod = $name;
    }

    public function getControllerMethodName(): string
    {
        return $this->requestedControllerMethod;
    }

    public function getMethod(): RequestMethod
    {
        return $this->method;
    }

    /**
     * Retrieves items from the request body
     * @param string $requestParam
     * @param mixed|null $notFoundDefaultValue The default value if the retrieved item is not in the request body
     * @return mixed
     */
    public function get(string $requestParam, mixed $notFoundDefaultValue = null): mixed
    {
        return $this->parameters[$requestParam] ?? $notFoundDefaultValue;
    }

    /**
     * Retrieves multiple request parameters from the request body.
     * @param array $requestParameters
     * @return array
     */
    public function getSome(array $requestParameters): array
    {
        $selection = [];
        foreach ($requestParameters as $param)
        {
            $selection[$param] = $this->get($param);
        }

        return $selection;
    }

    /**
     * Retrieves all parameters from the request body
     * @return array
     */
    public function getAll(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    private function processParameters(): void
    {
        if(ENV_CLI)
        {
            $callingArg = $GLOBALS['argv'][1] ?? '';
            if ($callingArg)
            {
                $paramSeparatorPos = strpos($callingArg, '?');
                if($paramSeparatorPos !== false)
                {
                    parse_str(substr($callingArg, $paramSeparatorPos + 1), $parameters);
                }
            }
            return;
        }

        $parameters = match ($this->method->name)
        {
            'GET' => $_GET,
            'POST' => $_POST,
            default => []
        };

        if(!$parameters)
        {
            $content = file_get_contents('php://input');
            if(!empty($content))
            {
                $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

                if(str_contains($contentType, HttpDataType::JSON->value))
                {
                    $json = json_decode($content, true);

                    if(!is_null($json))
                    {
                        $parameters = $json;
                    }
                }
                else if (str_contains($contentType, HttpDataType::X_WWW_FORM_URLENCODED->value))
                {
                    parse_str($content, $parameters);
                }
            }
        }

        $this->parameters = $parameters;
    }

    private function fetchHeaders(): void
    {
        $headers = [];
        foreach($_SERVER as $header => $val)
        {
            if(str_contains($header, 'HTTP_'))
            {
                $headers[str_replace('_', '-', substr($header, 5))] = $val;
            }
        }
        $this->headers = $headers;
    }
}