<?php
namespace Dominus\System;

use Dominus\Services\Http\Models\HttpDataType;
use Dominus\Services\Validator\Exceptions\InvalidValue;
use Dominus\Services\Validator\Exceptions\RuleNotFoundException;
use Dominus\Services\Validator\Validator;
use Dominus\System\Models\DominusFile;
use Dominus\System\Models\RequestMethod;
use stdClass;
use function fclose;
use function fgets;
use function fopen;
use function fread;
use function fwrite;
use function is_array;
use function is_null;
use function json_decode;
use function parse_str;
use function preg_match_all;
use function str_contains;
use function str_replace;
use function stripos;
use function strpos;
use function strtoupper;
use function substr;
use function trim;
use const APP_ENV_CLI;
use const DIRECTORY_SEPARATOR;

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
                $this->method = match(strtoupper($_SERVER['REQUEST_METHOD']))
                {
                    RequestMethod::POST->name => RequestMethod::POST,
                    RequestMethod::PUT->name => RequestMethod::PUT,
                    RequestMethod::DELETE->name => RequestMethod::DELETE,
                    RequestMethod::PATCH->name => RequestMethod::PATCH,
                    default => RequestMethod::GET
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
     * Get a file based on the form-data field name
     * @param string $fieldName
     * @return DominusFile|null
     */
    public function getFile(string $fieldName): ?DominusFile
    {
        return $this->files[$fieldName] ?? null;
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
     * @return stdClass|array
     */
    public function getAll(): stdClass|array
    {
        return $this->parameters;
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
            $parameters = match ($this->method->name)
            {
                'GET' => $_GET,
                'POST' => $_POST,
                default => []
            };

            if(!$parameters)
            {
                $phpInputStream = fopen('php://input', 'rb');

                if($phpInputStream && !feof($phpInputStream))
                {
                    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

                    if (stripos($contentType, HttpDataType::MULTIPART_FORM_DATA->value) !== false)
                    {
                        $formBoundary = strpos($contentType, 'boundary=');
                        if($formBoundary !== false)
                        {
                            list($parameters, $this->files) = $this->processMultipartFormData(
                                $phpInputStream,
                                (int)($_SERVER['CONTENT_LENGTH'] ?? 0),
                                substr($contentType, $formBoundary + 9)
                            );
                        }
                    }
                    else
                    {
                        $content = '';
                        while (!feof($phpInputStream))
                        {
                            $content .= fread($phpInputStream, 8192);
                        }

                        if(stripos($contentType, HttpDataType::JSON->value) !== false)
                        {
                            $json = json_decode($content, false);

                            if(!is_null($json))
                            {
                                $parameters = $json;
                            }
                        }
                        else if (stripos($contentType, HttpDataType::X_WWW_FORM_URLENCODED->value) !== false)
                        {
                            parse_str($content, $parameters);
                        }
                        else
                        {
                            $parameters = [$content];
                        }
                    }
                }

                fclose($phpInputStream);
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
        foreach ($_FILES as $fieldName => $file)
        {
            $this->files[$fieldName] = new DominusFile($file, true);
        }
    }

    /**
     * @param resource $dataStream
     * @param int $contentLength
     * @param string $boundary
     * @return array
     */
    private function processMultipartFormData(mixed $dataStream, int $contentLength, string $boundary): array
    {
        $formFields = [];
        $files = [];

        if(!$contentLength || !$boundary)
        {
            return $formFields;
        }

        $formEndBoundary = '--' . $boundary . "--\r\n";
        $boundary = '--' . $boundary . "\r\n";
        $uploadsTempDir = rtrim(ini_get('upload_tmp_dir') ?: sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        $fetchingDisposition = false;
        $fetchingContentType = false;
        $fetchingDataIn = -1;

        $contentType = '';
        $fieldName = '';
        $fileName = '';
        $fileHandler = null;
        $fileTempPath = '';
        $fileSize = 0;
        $data = '';

        while (!feof($dataStream))
        {
            $lineContents = fgets($dataStream);
            if($lineContents === false)
            {
                continue;
            }

            $endBoundaryHit = $lineContents === $formEndBoundary;
            if($lineContents === $boundary || $endBoundaryHit)
            {
                if($data)
                {
                    $formFields[$fieldName] = $data;
                }
                else if($fileHandler)
                {
                    fclose($fileHandler);
                    $fileHandler = null;
                    $files[$fieldName] = new DominusFile([
                        'name' => $fileName,
                        'type' => $contentType,
                        'tmp_name' => $fileTempPath,
                        'error' => 0,
                        'size' => $fileSize
                    ], false);
                }

                if($endBoundaryHit)
                {
                    break;
                }

                $fetchingDisposition = true;
                $fetchingDataIn = -1;
                $fieldName = '';
                $fileName = '';
                $contentType = '';
                $fileTempPath = '';
                $fileSize = 0;
                $data = '';
            }
            else if($fetchingDisposition)
            {
                $fetchingDisposition = false;
                $fetchingContentType = true;
                preg_match_all('/name="([^"]*)"|filename="([^"]*)"/', $lineContents, $fieldMetadata);
                $fieldName = $fieldMetadata[1][0] ?? '';
                if(!$fieldName)
                {
                    continue;
                }
                $fileName = $fieldMetadata[2][1] ?? '';
            }
            else if($fetchingContentType)
            {
                $fetchingContentType = false;
                $contentType = str_replace('Content-Type: ', '', trim($lineContents, "\r\n"));
                $fetchingDataIn = $contentType === '' ? 1 : 2;
            }
            else if ($fetchingDataIn === 0 || ($fetchingDataIn > -1 && (--$fetchingDataIn === 0)))
            {
                if($fileName)
                {
                    if(!$fileHandler)
                    {
                        $fileTempPath = $uploadsTempDir . DIRECTORY_SEPARATOR . $fileName;
                        $fileHandler = fopen($fileTempPath, 'wb');
                    }

                    $bytesWritten = fwrite($fileHandler, $lineContents);
                    if($bytesWritten === false)
                    {
                        continue;
                    }

                    $fileSize += $bytesWritten;
                }
                else
                {
                    $data .= substr($lineContents, 0, strrpos($lineContents, "\r\n") ?: null);
                }
            }
        }

        return [$formFields, $files];
    }
}