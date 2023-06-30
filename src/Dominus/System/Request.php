<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpDataType;
use Dominus\Services\Validator\Exceptions\InvalidValue;
use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\Services\Validator\Validator;
use Dominus\System\Models\DominusFile;
use Dominus\System\Models\RequestMethod;
use stdClass;
use function file_get_contents;
use function is_array;
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
    private bool $paramsAsArray = false;

    /**
     * @param RequestMethod|null $method
     * @param array|stdClass|null $parameters
     * @param string $requestedController
     * @param string $requestedControllerMethod
     * @param DominusFile[] $files
     */
    public function __construct(
        private ?RequestMethod $method = null,
        private array|stdClass|null $parameters = null,
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
        else
        {
            $this->paramsAsArray = is_array($this->parameters);
        }

        if(!empty($_FILES))
        {
            $this->processFiles();
        }
    }

    /**
     * @param string $header required header i.e. X-REQUESTED-WITH
     * @return string|null
     */
    public function getHeader(string $header): ?string
    {
        return $this->headers[strtoupper($header)] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setControllerName(string $name): Request
    {
        $this->requestedController = $name;
        return $this;
    }

    public function getControllerName(): string
    {
        return $this->requestedController;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setControllerMethodName(string $name): Request
    {
        $this->requestedControllerMethod = $name;
        return $this;
    }

    public function getControllerMethodName(): string
    {
        return $this->requestedControllerMethod;
    }

    /**
     * Retrieves the request method
     * @return RequestMethod
     */
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
        if($this->paramsAsArray)
        {
            return $this->parameters[$requestParam] ?? $notFoundDefaultValue;
        }

        return $this->parameters->$requestParam ?? $notFoundDefaultValue;
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
     * Validate request parameters using given validation rules
     *
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
     * @param bool $toAssociativeArray Converts the request body to an associative array
     * @return array|stdClass
     */
    public function getAll(bool $toAssociativeArray = true): array|stdClass
    {
        return $toAssociativeArray ? (array)$this->parameters : $this->parameters;
    }

    /**
     * Overwrite all request parameters
     * @param array|stdClass $parameters
     * @return $this
     */
    public function setParameters(array|stdClass $parameters): Request
    {
        $this->parameters = $parameters;
        $this->paramsAsArray = is_array($parameters);
        return $this;
    }

    /**
     * Add or replace request parameters
     *
     * @param string $parameter
     * @param mixed $value
     * @return $this
     */
    public function setParameter(string $parameter, mixed $value): Request
    {
        if($this->paramsAsArray)
        {
            $this->parameters[$parameter] = $value;
        }
        else
        {
            $this->parameters->$parameter = $value;
        }

        return $this;
    }

    private function processParameters(): void
    {
        $parameters = [];

        if(APP_ENV_CLI)
        {
            $scriptArguments = $GLOBALS['argv'][1] ?? '';
            if ($scriptArguments)
            {
                $paramSeparatorPos = strpos($scriptArguments, '?');
                if($paramSeparatorPos !== false)
                {
                    parse_str(substr($scriptArguments, $paramSeparatorPos + 1), $parameters);
                }
            }
        }
        else
        {
            $content = file_get_contents('php://input');
            if(!empty($content))
            {
                $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

                if(str_contains($contentType, HttpDataType::JSON->value))
                {
                    $json = json_decode($content);

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

            if(!$parameters)
            {
                $parameters = match ($this->method->name)
                {
                    'GET' => $_GET,
                    'POST' => $_POST,
                    default => []
                };
            }
        }

        $this->setParameters($parameters);
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