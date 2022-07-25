<?php

namespace GoDaddy\WordPress\MWC\Common\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\HttpRepository;
use stdClass;

/**
 * HTTP Request handler.
 */
class Request
{
    /** @var array request body */
    public $body;

    /** @var array request headers */
    public $headers;

    /** @var string request method */
    public $method;

    /** @var object|array request query parameters */
    public $query;

    /** @var bool whether should verify SSL */
    public $sslVerify;

    /** @var int default timeout in seconds */
    public $timeout;

    /** @var string the URL to send the request to */
    public $url;

    /** @var array allowed request method types */
    protected $allowedMethodTypes = ['GET', 'POST', 'HEAD', 'PUT', 'DELETE', 'TRACE', 'OPTIONS', 'PATCH'];

    /** @var string default allowed method */
    protected $defaultAllowedMethod = 'get';

    /** @var stdClass the type of response the request should return */
    protected $responseClass = Response::class;

    /**
     * Request constructor.
     *
     * @param string|null $url
     * @throws Exception
     */
    public function __construct(string $url = null)
    {
        $this->setHeaders()
            ->setMethod()
            ->sslVerify()
            ->setTimeout();

        if ($url) {
            $this->setUrl($url);
        }
    }

    /**
     * @deprecated Please use setBody()
     */
    public function body(array $body) : Request
    {
        return $this->setBody($body);
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws Exception
     */
    protected function buildUrlString() : string
    {
        $queryString = ! empty($this->query) ? '?'.ArrayHelper::query($this->query) : '';

        return $this->url.$queryString;
    }

    /**
     * @throws Exception
     * @deprecated use setHeaders()
     */
    public function headers($additionalHeaders = []) : Request
    {
        return $this->setHeaders($additionalHeaders);
    }

    /**
     * Sets the request method.
     *
     * @param string|null $method
     * @return Request
     */
    public function setMethod(string $method = null) : Request
    {
        if (! $method || ! ArrayHelper::contains($this->allowedMethodTypes, strtoupper($method))) {
            $method = $this->defaultAllowedMethod ?? 'get';
        }

        $this->method = strtoupper($method);

        return $this;
    }

    /**
     * @deprecated use setQuery()
     */
    public function query(array $params) : Request
    {
        return $this->setQuery($params);
    }

    /**
     * Sends the request.
     *
     * @return Response
     * @throws Exception
     */
    public function send() : Response
    {
        $this->validate();

        return new $this->responseClass(HttpRepository::performRequest(
            $this->buildUrlString(),
            [
                'body'      => $this->body ? json_encode($this->body) : null,
                'headers'   => $this->headers,
                'method'    => $this->method,
                'sslverify' => $this->sslVerify,
                'timeout'   => $this->timeout,
            ]
        ));
    }

    /**
     * Sets the body of the request.
     *
     * @param array $body
     * @return Request
     */
    public function setBody(array $body) : Request
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Sets Request headers.
     *
     * @param array|null $additionalHeaders
     * @return Request
     * @throws Exception
     */
    public function setHeaders($additionalHeaders = []) : Request
    {
        $this->headers = ArrayHelper::combine(['Content-Type' => 'application/json'], $additionalHeaders);

        return $this;
    }

    /**
     * Sets query parameters.
     *
     * @param array $params
     * @return Request
     */
    public function setQuery(array $params) : Request
    {
        $this->query = $params;

        return $this;
    }

    /**
     * Sets the request timeout.
     *
     * @param int $seconds
     * @return Request
     */
    public function setTimeout(int $seconds = 30) : Request
    {
        $this->timeout = $seconds;

        return $this;
    }

    /**
     * Sets the url of the request.
     *
     * @param string $url
     * @return Request
     */
    public function setUrl(string $url) : Request
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Sets SSL verify.
     *
     * @param bool $default
     * @return $this
     * @throws Exception
     */
    public function sslVerify($default = false) : Request
    {
        $this->sslVerify = $default || ManagedWooCommerceRepository::isProductionEnvironment();

        return $this;
    }

    /**
     * @deprecated use setTimeout()
     */
    public function timeout(int $seconds = 30) : Request
    {
        return $this->setTimeout($seconds);
    }

    /**
     * @deprecated use setUrl()
     */
    public function url(string $url) : Request
    {
        return $this->setUrl($url);
    }

    /**
     * Validates the request.
     *
     * @throws Exception
     */
    protected function validate()
    {
        if (! $this->url) {
            throw new Exception('You must provide a url for an outgoing request');
        }
    }
}
