<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\UriInterface;
use PHPFuse\Http\Uri;
use PHPFuse\Output\Format;

class Request extends Message implements RequestInterface
{
    private $uriParts;
    
    private $method;
    private $path;
    private $uriInst;
    private $requestTarget;
    private static $requestHeaders;

    protected $env;
    protected $headers;
    
    function __construct(?UriInterface $uriInst = NULL) 
    {

        $this->headers = static::requestHeaders();
        parent::__construct();
        $this->env = $_SERVER;
        $this->uriInst = (is_null($uriInst)) ? Uri::withUriParts($this->getUriEnv()) : $uriInst;
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
     * Get URI enviment Part data
     * @return array
     */
    function getUriEnv(): array
    {
        if(is_null($this->uriParts)) {
            $this->uriParts['scheme'] = ($this->getEnv("HTTPS") === 'on') ? 'https' : 'http';
            $this->uriParts['user'] = $this->getEnv("PHP_AUTH_USER");
            $this->uriParts['pass'] = $this->getEnv("PHP_AUTH_PW");
            $this->uriParts['host'] = ($host = $this->getEnv("HTTP_HOST")) ? $host : $this->getEnv("SERVER_NAME");
            $this->uriParts['port'] = $this->getEnv("SERVER_PORT", NULL);
            $this->uriParts['path'] = $this->getEnvPath();
            $this->uriParts['query'] = $this->getEnv("QUERY_STRING");
            $this->uriParts['fragment'] = $this->getEnv("QUERY_STRING");
            if(!is_null($this->uriParts['port'])) $this->uriParts['port'] = (int)$this->uriParts['port']; 
        }
        return $this->uriParts;
    }

    /**
     * Get request/server environment data
     * @param  string $key     Server key
     * @param  string $default Default value, returned if Env data is empty
     * @return string|null
     */
    function getEnv(string $key, ?string $default = ""): ?string 
    {
        return ($this->env[$key] ?? $default);
    }

    /**
     * Check if environment data exists
     * @param  string  $key Server key
     * @return boolean
     */
    function hasEnv($key): bool 
    {
        return (bool)($this->getEnv($key, NULL));
    }

    /**
     * Build and return URI Path from environment
     * @return string
     */
    function getEnvPath(): string 
    {
        if(is_null($this->path)) {
            $basePath = '';
            $requestName = Format\Uri::value($this->getEnv("SCRIPT_NAME"))->extractPath()->get();
            $requestDir = dirname($requestName);
            $requestUri = Format\Uri::value($this->getEnv("REQUEST_URI"))->extractPath()->get();

            $this->path = $requestUri;
            if (stripos($requestUri, $requestName) === 0) {
                $basePath = $requestName;
            } elseif ($requestDir !== '/' && stripos($requestUri, $requestDir) === 0) {
                $basePath = $requestDir;
            }
            if($basePath) $this->path = ltrim(substr($requestUri, strlen($basePath)), '/');
        }
        return $this->path;
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
        return static::$requestHeaders;
    }

}
