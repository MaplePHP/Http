<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\UriInterface;
use PHPFuse\Http\Interfaces\StreamInterface;
use PHPFuse\Http\Uri;


class Request extends Message implements RequestInterface
{
    private $method;
    private $uriInst;
    private $requestTarget;
    private static $requestHeaders;
    protected $headers;
    
    function __construct(UriInterface $uriInst, ?StreamInterface $stream = NULL) 
    {
        $this->headers = static::requestHeaders();
        parent::__construct($stream);
        $this->uriInst = $uriInst;
    }

    /**
     * Get the message request target
     * @return string
     */
    public function getRequestTarget(): string 
    {
        if(is_null($this->requestTarget)) {
            $parts = $this->getUriEnv();
            $this->requestTarget = $parts['path'].(($parts['query']) ? "?{$parts['query']}" : "");
        }
        return $this->requestTarget;
    }

    /**
     * Return an instance with the specific set requestTarget
     * @param  string $requestTarget
     * @return RequestInterface
     */
    public function withRequestTarget(string $requestTarget): RequestInterface 
    {
        $inst = clone $this;
        $inst->requestTarget = $requestTarget;
        return $inst;
    }

    /**
     * Get the message request method (always as upper case)
     * @return string
     */
    public function getMethod(): string
    {
        if(is_null($this->method)) $this->method = strtoupper($this->getEnv("REQUEST_METHOD"));
        return $this->method;
    }

    /**
     * Return an instance with the specific set Method
     * @param  string $requestTarget
     * @return RequestInterface
     */
    public function withMethod(string $method): RequestInterface
    {
        $inst = clone $this;
        $inst->method = strtoupper($method);
        return $inst;
    }

    /**
     * Get URI instance with set request messege
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uriInst;
    }

    /**
     * Return an instance with the with a new instance of UriInterface set
     * @param  UriInterface $uri          Instance of UriInterface
     * @param  boolean      $preserveHost Preserve the current request header Host
     * @return RequestInterface
     */
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface 
    {
        $inst = clone $this;
        if($preserveHost) $uri->withHost($this->getHeader("Host"));
        $inst->uriInst = $url;
        return $inst;
    }

    /**
     * Get all request headers
     * @return array
     */
    public static function requestHeaders(): array 
    {
        if(is_null(static::$requestHeaders)) {
            if(function_exists("getallheaders")) {
                static::$requestHeaders = getallheaders();
            } else {
                static::$requestHeaders = array();
                foreach($_SERVER as $key => $value) {
                    if (substr($key, 0, 5) <> 'HTTP_') {
                        continue;
                    }
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    static::$requestHeaders[$header] = $value;
                }
            }
        }
        static::$requestHeaders = array_change_key_case(static::$requestHeaders);
        return static::$requestHeaders;
    }

    /**
     * Chech if is request is SSL
     * @return boolean [description]
     */
    function isSSL(): bool 
    {
        $https = strtolower($this->getEnv("HTTPS"));
        return (bool)($https === "on" || $https === "1" || $this->getPort() === 443);
    }

    /**
     * Get Server request port
     * @return int
     */
    function getPort(): int 
    {
        $port = (int)(($p = $this->getEnv("SERVER_PORT")) ? $p : $this->uriInst->getPort());
        return (int)$port;
    }

}
