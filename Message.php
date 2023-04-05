<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\MessageInterface;
use PHPFuse\Http\Interfaces\StreamInterface;
use PHPFuse\Output\Format;

abstract class Message implements MessageInterface
{

    protected $server;
    protected $serverProtocol;
    protected $version;
    protected $headers = array();
    protected $body;
    protected $uriParts;
    protected $env;
    protected $path;

    function __construct(?StreamInterface $body = NULL) 
    {
        $this->env = $_SERVER;
        $this->serverProtocol = ($this->env['SERVER_PROTOCOL'] ?? "HTTP/1.1");
        $this->body = $body;
    }

    public function getProtocolVersion() 
    {

        if(is_null($this->version)) {
            $prot = explode("/", $this->serverProtocol);
            $this->version = end($prot);
        }

        return $this->version;
    }

    public function withProtocolVersion($version) 
    {
        $inst = clone $this;
        $inst->version = $version;
        return $inst;

    }

    public function getHeaders() 
    {
        return $this->headers;
    }

    public function hasHeader($name) 
    {
        return (bool)($this->headers[$name] ?? NULL);
    }

    public function getHeader($name) 
    {
        return ($this->headers[$name] ?? NULL);
    }

    public function getHeaderLine($name) 
    {

    }

    public function withHeader($name, $value) 
    {
        $inst = clone $this;
        $inst->headers[$name] = $value;
        return $inst;
    }

    public function withAddedHeader($name, $value) 
    {

    }

    public function withoutHeader($name) 
    {

    }

    public function getBody() 
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body) 
    {
        $inst = clone $this;
        $inst->body = $body;
        return $inst;
    }

    /**
     * Get URI enviment Part data that will be passed to UriInterface and match to public object if exists.
     * @return array
     */
    function getUriEnv(array $add): array
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
            $this->uriParts = array_merge($this->uriParts, $add);
        }
        return $this->uriParts;
    }

    function setUriEnv($key, $value): void 
    {
        $this->uriParts[$key] = $value;
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

}
