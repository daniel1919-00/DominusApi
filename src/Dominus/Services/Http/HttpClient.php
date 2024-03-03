<?php
namespace Dominus\Services\Http;

use CurlHandle;
use Dominus\Services\Http\Models\HttpDataType;
use Dominus\System\Interfaces\Injectable\Injectable;
use function curl_getinfo;
use function curl_setopt;
use function env;
use function explode;
use function http_build_query;
use function json_encode;
use function stripos;
use function strlen;
use function strtoupper;
use function trim;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADERFUNCTION;
use const CURLOPT_POSTFIELDS;

/**
 * Injectable wrapper for Curl
 */
class HttpClient implements Injectable
{
    private CurlHandle $curlHandle;
    private int $timeout;
    private array $headers;
    private array $cookies;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Generates a Content-Type header key
     * Example: Content-Type: application/json; charset=utf-8
     *
     * @param HttpDataType $contentType
     * @param string $charset
     * @return string
     */
    public static function getContentTypeHeader(HttpDataType $contentType, string $charset = 'utf-8'): string
    {
        return "Content-Type: $contentType->value; charset=$charset";
    }

    /**
     * Sets the contents of the “User-Agent: ” header to be used in the HTTP request.
     * @param string $userAgent
     * @return $this
     */
    public function setUserAgent(string $userAgent): static
    {
        $this->setOption(CURLOPT_USERAGENT, $userAgent);
        return $this;
    }

    /**
     * Set contents of HTTP Cookie header.
     *
     * @param string $key   The name of the cookie
     * @param string $value The value for the provided cookie name
     * @return self
     */
    public function setCookie(string $key, string $value): static
    {
        $this->cookies[$key] = $value;
        return $this;
    }

    /**
     * Set contents of HTTP Cookie header.
     * @param array $cookies An array of the form: [cookieName => cookieValue]
     * @return $this
     */
    public function setCookies(array $cookies): static
    {
        $this->cookies = array_merge($this->cookies, $cookies);
        return $this;
    }

    /**
     * Retrieve the stored cookies
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Set customized curl options.
     * @see http://php.net/curl_setopt
     *
     * @param int $option The curl option constant e.g. `CURLOPT_AUTOREFERER`, `CURLOPT_COOKIESESSION`
     * @param mixed $value The value to pass for the given $option
     * @return bool
     */
    public function setOption(int $option, mixed $value): bool
    {
        return curl_setopt($this->curlHandle, $option, $value);
    }

    /**
     * Set the number of seconds the request wil be allowed to execute before forced termination
     * @param int $timeoutSeconds Default: 60 seconds
     */
    public function setTimeout(int $timeoutSeconds): static
    {
        $this->timeout = $timeoutSeconds;
        $this->setOption(CURLOPT_TIMEOUT, $timeoutSeconds);
        return $this;
    }

    /**
     * @param array $headers An array of HTTP header fields to set, in the format ['Content-type: text/plain', 'Content-length: 100']
     * @return $this
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param string $header The header to add. Example: 'Content-type: text/plain'
     * @return $this
     */
    public function addHeader(string $header): static
    {
        $this->headers[] = $header;
        return $this;
    }

    /**
     * @param string $uri
     * @param array $queryParameters
     * @return HttpResponse
     */
    public function get(string $uri, array $queryParameters = []): HttpResponse
    {
        return $this->request('GET', $uri, $queryParameters);
    }

    /**
     * @param string $uri
     * @param mixed|null $data Will automatically be json-encoded if the $dataType is HttpDataType::JSON or passed to the CURLOPT_POSTFIELDS as is.
     * @param HttpDataType $dataType
     * @return HttpResponse
     */
    public function post(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('POST', $uri, $data, $dataType);
    }

    /**
     * @param string $uri
     * @param mixed|null $data Will automatically be json-encoded if the $dataType is HttpDataType::JSON or passed to the CURLOPT_POSTFIELDS as is.
     * @param HttpDataType $dataType
     * @return HttpResponse
     */
    public function put(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('PUT', $uri, $data, $dataType);
    }

    /**
     * @param string $uri
     * @param mixed|null $data Will automatically be json-encoded if the $dataType is HttpDataType::JSON or passed to the CURLOPT_POSTFIELDS as is.
     * @param HttpDataType $dataType
     * @return HttpResponse
     */
    public function delete(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('DELETE', $uri, $data, $dataType);
    }

    /**
     * @param string $method
     * @param string $requestUri
     * @param mixed $data Will automatically be json-encoded if the $dataType is HttpDataType::JSON or passed to the CURLOPT_POSTFIELDS as is.
     * @param HttpDataType $dataType
     * @return HttpResponse
     */
    public function request(string $method, string $requestUri, mixed $data, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        $method = strtoupper($method);
        $headers = $this->headers;
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => $method
        ];

        if($this->cookies)
        {
            $curlOptions[CURLOPT_COOKIE] = http_build_query($this->cookies, '', '; ');
        }

        if($data)
        {
            if($method === 'GET')
            {
                $requestUri .= '?' . http_build_query($data);
            }
            else
            {
                if($dataType !== HttpDataType::MULTIPART_FORM_DATA)
                {
                    $headers[] = self::getContentTypeHeader($dataType);
                }

                $requestBody = match ($dataType)
                {
                    HttpDataType::JSON => json_encode($data),
                    HttpDataType::X_WWW_FORM_URLENCODED => http_build_query($data),
                    HttpDataType::TEXT,
                    HttpDataType::HTML,
                    HttpDataType::XML,
                    HttpDataType::MULTIPART_FORM_DATA => $data
                };

                $curlOptions[CURLOPT_POSTFIELDS] = $requestBody;
            }
        }

        $curlOptions[CURLOPT_URL] = $requestUri;

        if($headers)
        {
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        }

        $ch = $this->curlHandle;
        curl_setopt_array($ch, $curlOptions);
        $responseHeaders = [];
        $responseCookies = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function($curl, $header) use (&$responseHeaders, &$responseCookies)
        {
            $headerComponents = explode(':', trim($header), 2);
            $key = $headerComponents[0] ?? '';
            $value = $headerComponents[1] ?? '';
            if($key !== '' && $value !== '')
            {
                $responseHeaders[$key] = $value;
                if(stripos($key, 'Set-Cookie') === 0)
                {
                    $cookies = explode(';', $value);
                    foreach ($cookies as $cookie)
                    {
                        $cookieComponents = explode('=', $cookie,  2);
                        if(!empty($cookieComponents[0]))
                        {
                            $responseCookies[$cookieComponents[0]] = $cookieComponents[1] ?? '';
                        }
                    }
                }
            }

            return strlen($header);
        });

        $response = curl_exec($ch);
        $errorCode = curl_errno($ch);
        $errorMessage = curl_error($ch);
        $statusCode = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));

        $this->setCookies($responseCookies);

        return new HttpResponse(
            $response === false || $errorCode || ($statusCode >= 400 && $statusCode < 600),
            $errorCode,
            $errorMessage,
            $statusCode,
            $response === false ? '' : $response,
            $responseHeaders
        );
    }

    /**
     * Resets all the configuration for this client (cookies, headers, etc...)
     * @return void
     */
    public function reset(): void
    {
        $this->timeout = (int)env('SERVICES_HTTP_CONNECTION_TIMEOUT', '30');
        $this->cookies = [];
        $this->headers = [];
        $this->init();
    }

    private function init(): void
    {
        if(isset($this->curlHandle))
        {
            curl_close($this->curlHandle);
        }
        $this->curlHandle = curl_init();
        curl_setopt_array($this->curlHandle, [
            CURLOPT_VERBOSE => env('SERVICES_HTTP_DEBUG', 'false') === 'true',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSH_COMPRESSION => true,
            CURLOPT_FOLLOWLOCATION => env('SERVICES_HTTP_FOLLOW_LOCATION', 'true') === 'true',
            CURLOPT_HEADER => env('SERVICES_HTTP_OUTPUT_INCLUDE_HEADER', 'false') === 'true',
            CURLINFO_HEADER_OUT => true,
            CURLOPT_USERAGENT => env('SERVICES_HTTP_USERAGENT', 'Dominus API Http Client'),
            CURLOPT_CONNECTTIMEOUT => (int)env('SERVICES_HTTP_CONNECT_TIMEOUT', '30'),
            CURLOPT_SSL_VERIFYHOST => env('SERVICES_HTTP_SSL_VERIFY_HOST', 'true') === 'false' ? 0 : 2,
            CURLOPT_SSL_VERIFYPEER => env('SERVICES_HTTP_SSL_VERIFY_PEER', 'true') === 'true'
        ]);
    }

    public function __destruct()
    {
        curl_close($this->curlHandle);
    }
}