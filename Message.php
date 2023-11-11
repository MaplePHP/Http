<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\MessageInterface;
use PHPFuse\Http\Interfaces\StreamInterface;
use PHPFuse\DTO\Format;

abstract class Message implements MessageInterface
{
    protected $server;
    //protected $serverProtocol;
    protected $version;
    protected $headers;
    protected $body;
    protected $uriParts;
    protected $env;
    protected $path;
    protected $headerLine;

    /**
     * Get server HTTP protocol version number
     * @return string
     */
    public function getProtocolVersion()
    {
        if (is_null($this->version)) {
            $prot = explode("/", ($this->env['SERVER_PROTOCOL'] ?? "HTTP/1.1"));
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
    public function getHeaders(): array
    {
        return $this->headers->getHeaders();
    }

    /**
     * Get header from name/key
     * @param  string $name name/key (case insensitive)
     * @return array
     */
    public function getHeader($name): array
    {
        return $this->headers->getHeader($name);
    }


    /**
     * Check is a header exists
     * @param  string  $name Header name/key (case insensitive)
     * @return boolean
     */
    public function hasHeader($name): bool
    {
        if (is_null($this->headers)) {
            throw new \Exception("Missing The HTTP Headers instance", 1);
        }
        return $this->headers->hasHeader($name);
    }

    /**
     * Get header value line
     * @param  string $name name/key (case insensitive)
     * @return string
     */
    public function getHeaderLine($name)
    {
        $data = $this->getHeaderLineData($name);
        if (!is_array($data)) {
            throw new \Exception("The header line is not an array!", 1);
        }
        return (count($data) > 0) ? implode("; ", $data) : "";
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
        if (is_array($headerArr)) {
            foreach ($headerArr as $key => $val) {
                if (is_numeric($key)) {
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
        foreach ($arr as $key => $val) {
            $inst = $inst->withHeader($key, $val);
        }
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
        $inst->headers->setHeader($name, $value);
        return $inst;
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
        $inst->headers->setHeader($name, $value);
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
        $inst->headers->deleteHeader($name);
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
}
