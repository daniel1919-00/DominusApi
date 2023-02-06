<?php
/**
 * @noinspection PhpComposerExtensionStubsInspection
 * @noinspection PhpUnused
 */

namespace Dominus\Services\Http;

use CurlHandle;
use Dominus\Services\Http\Models\HttpDataType;
use Dominus\System\Interfaces\Injectable\Injectable;

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
     * @param array $cookies An array of this form: [cookieName => cookieValue]
     * @return $this
     */
    public function setCookies(array $cookies): static
    {
        $this->cookies = array_merge($this->cookies, $cookies);
        return $this;
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
     * @param array $headers A collection of headers
     * @return $this
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    public function addHeader(string $header): static
    {
        $this->headers[] = $header;
        return $this;
    }

    public function get(string $uri, array $queryParameters = []): HttpResponse
    {
        if($queryParameters)
        {
            $uri .= '?' . http_build_query($queryParameters);
        }
        return $this->request('GET', $uri, null);
    }

    public function post(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('POST', $uri, $data, $dataType);
    }

    public function put(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('PUT', $uri, $data, $dataType);
    }

    public function delete(string $uri, mixed $data = null, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        return $this->request('DELETE', $uri, $data, $dataType);
    }

    /**
     * @param string $method
     * @param string $requestUri
     * @param mixed $data
     * @param HttpDataType $dataType
     * @return HttpResponse
     */
    public function request(string $method, string $requestUri, mixed $data, HttpDataType $dataType = HttpDataType::JSON): HttpResponse
    {
        $headers = $this->headers;
        $curlOptions = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => $requestUri
        ];

        if($this->cookies)
        {
            $curlOptions[CURLOPT_COOKIE] = http_build_query($this->cookies, '', '; ');
        }

        if($data)
        {
            $headers[] = self::getContentTypeHeader($dataType);
            $curlOptions[CURLOPT_POSTFIELDS] = $dataType == HttpDataType::JSON ? json_encode($data) : $data;
        }

        if($headers)
        {
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        }

        $ch = $this->curlHandle;
        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $errorCode = curl_errno($ch);
        $errorMessage = curl_error($ch);
        $statusCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        return new HttpResponse(
            $errorCode !== 0 || ($statusCode >= 400 && $statusCode < 600),
            $errorCode,
            $errorMessage,
            $statusCode,
            $response
        );
    }

    /**
     * Resets all the configuration for this client (cookies, headers, etc...)
     * @return void
     */
    public function reset(): void
    {
        $this->timeout = (int)env('SERVICES_HTTP_CONNECTION_TIMEOUT', 30);
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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSH_COMPRESSION => true,
            CURLOPT_HEADER => false,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_USERAGENT => env('SERVICES_HTTP_USERAGENT'),
            CURLOPT_CONNECTTIMEOUT => (int)env('SERVICES_HTTP_CONNECT_TIMEOUT', 30),
            CURLOPT_SSL_VERIFYHOST => env('SERVICES_HTTP_SSL_VERIFY_HOST') === 'false' ? 0 : 2,
            CURLOPT_SSL_VERIFYPEER => env('SERVICES_HTTP_SSL_VERIFY_HOST') === 'true'
        ]);
    }

    public function __destruct()
    {
        curl_close($this->curlHandle);
    }
}