<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpDataType;
use Dominus\Services\Validator\Exceptions\InvalidValue;
use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\Services\Validator\Validator;
use Dominus\System\Models\DominusFile;
use Dominus\System\Models\RequestMethod;
use function file_get_contents;
use function is_null;
use function json_decode;
use function parse_str;
use function str_contains;
use function str_replace;
use function strpos;
use function strtolower;
use function strtoupper;
use function substr;
use const APP_ENV_CLI;

final class Request
{
    private array $headers = [];

    /**
     * @param RequestMethod|null $method
     * @param array|null $parameters
     * @param string $requestedController
     * @param string $requestedControllerMethod
     * @param DominusFile[] $files
     */
    public function __construct(
        private ?RequestMethod $method = null,
        private ?array $parameters = null,
        private string $requestedController = '',
        private string $requestedControllerMethod = '',
        private array $files = []
    )
    {
        if(!APP_ENV_CLI)
        {
            if(!$method)
            {
                $this->method = match(strtoupper($_SERVER['REQUEST_METHOD'])) {
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

        if(!empty($_FILES))
        {
            $this->processFiles();
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
     * Returns any files uploaded with this request
     * @return DominusFile[]
     */
    public function getFiles(): array
    {
        return $this->files;
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
     * @param array $validationRules An array of validation rules. Example: ['data_field_to_validate' => 'rule1|rule2:rule2_arg1:rule2_arg2|rule3']
     *
     * @return array array An array containing the fields that did not pass validation and the corresponding rules that failed. Example: ['data_field_1' => ['rule1', 'rule2']]
     * In this example, the field 'data_field_1' did not pass the following validation rules: 'rule1' and 'rule2'.
     *
     * @throws InvalidValue
     * @throws RuleNotFoundException
     */
    public function validate(array $validationRules): array
    {
        return (new Validator())->validate($this->parameters, $validationRules);
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
        if(APP_ENV_CLI)
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

    private function processFiles(): void
    {
        foreach ($_FILES as $file)
        {
            $this->files[] = new DominusFile($file);
        }
    }
}