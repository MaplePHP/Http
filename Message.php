<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\MessageInterface;
use PHPFuse\Http\Interfaces\StreamInterface;
use PHPFuse\DTO\Format;

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
    protected $headerLine;

    function __construct(?StreamInterface $body = NULL) 
    {
        $this->env = $_SERVER;
        $this->serverProtocol = ($this->env['SERVER_PROTOCOL'] ?? "HTTP/1.1");
        $this->body = $body;
    }

    /**
     * Get server HTTP protocol version number 
     * @return string
     */
    public function getProtocolVersion() 
    {
        if(is_null($this->version)) {
            $prot = explode("/", $this->serverProtocol);
            $this->version = end($prot);
        }

        return $this->version;
    }

    /**
     * Set new server HTTP protocol version number 
     * @param  string $version
     * @return static
     */
    public function withProtocolVersion($version) 
    {
        $inst = clone $this;
        $inst->version = $version;
        return $inst;
    }

    /**
     * Get all current headers
     * @return array
     */
    public function getHeaders() 
    {
        return $this->headers;
    }

    /**
     * Check is a header exists 
     * @param  string  $name Header name/key (case insensitive)
     * @return boolean
     */
    public function hasHeader($name) 
    {
        $name = $this->headerKey($name);
        return (bool)($this->headers[$name] ?? NULL);
    }

    /**
     * Get header from name/key
     * @param  string $name name/key (case insensitive)
     * @return array
     */
    public function getHeader($name) 
    {
        $name = $this->headerKey($name);
        return ($this->headers[$name] ?? []);
    }

    /**
     * Get header value line
     * @param  string $name name/key (case insensitive)
     * @return string
     */
    public function getHeaderLine($name) 
    {
        $data = $this->getHeaderLineData($name);
        return (count($data) > 0) ? implode("; ", $data) : ($data[0] ?? "");
    }

    /**
     * Get header value data items
     * @param  string $name name/key (case insensitive)
     * @return null||array
     */
    public function getHeaderLineData(string $name): ?array 
    {
        $this->headerLine = array();
        $headerArr = $this->getHeader($name);
        if(is_array($headerArr)) {
            foreach($headerArr as $key => $val) {
                if(is_numeric($key)) {
                    $this->headerLine[] = $val;
                } else {
                    $this->headerLine[] = "{$key} {$val}";
                }
            }

        } else {
            $this->headerLine[] = $headerArr;
        }
        
        return $this->headerLine;
    }

    /**
     * Set multiple headers
     * @param  array  $arr
     * @return static
     */
    public function withHeaders(array $arr) 
    {
        $inst = $this;
        foreach($arr as $key => $val) $inst = $inst->withHeader($key, $val);
        return $inst;
    }

    /**
     * Set new header
     * @param  string $name 
     * @param  string/array $value
     * @return static
     */
    public function withHeader($name, $value) 
    {
        $inst = clone $this;
        $inst->setHeader($name, $value);
        $inst->resetHeaderLine();
        return $inst;
    }

    /**
     * Set new header
     * @param  string $name 
     * @param  string/array $value
     * @return static
     */
    public function setHeader($name, $value) 
    {
        $name = $this->headerKey($name);
        $this->headers[$name] = is_array($value) ? $value : array_map('trim', explode(';', $value));
        return $this->headers;
    }

    /**
     * Set new headers
     * @param  string $name 
     * @param  string/array $value
     * @return static
     */
    public function setHeaders(array $arr): array 
    {
        foreach($arr as $key => $val) $this->setHeader($key, $val);
        return $this->headers;
    }

    /**
     * Add header line
     * @param  string $name  name/key (case insensitive)
     * @param  string $value
     * @return static
     */
    public function withAddedHeader($name, $value) 
    {
        $inst = clone $this;
        if($inst->hasHeader($name)) $inst->headers[$name][] = $value;
        $inst->setHeader($name, $inst->headers[$name]);
        $inst->resetHeaderLine();
        return $inst;
    }

    /**
     * Unset/remove header
     * @param  string $name name/key (case insensitive)
     * @return static
     */
    public function withoutHeader($name) 
    {
        $inst = clone $this;
        $name = $this->headerKey($name);
        if(isset($inst->headers[$name])) unset($inst->headers[$name]);
        $inst->resetHeaderLine();
        return $inst;

    }

    /**
     * Get stream body
     * @return StreamInterface
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Set new stream body
     * @param  StreamInterface $body
     * @return static
     */
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

    /**
     * Used to make header keys consistent 
     * @param  string $key
     * @return string
     */
    protected function headerKey(string $key): string 
    {
        return strtolower($key);
    }

    protected function resetHeaderLine(): void 
    {
        $this->headerLine = NULL;
    }

}
