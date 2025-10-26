<?php

namespace MaplePHP\Http;

use MaplePHP\Http\Exceptions\RequestException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

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
    public function getProtocolVersion(): string
    {
        if ($this->version === null) {
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
    public function withProtocolVersion(string $version): MessageInterface
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
     * @param string $name Header name/key (case-insensitive)
     * @return boolean
     * @throws RequestException
     */
    public function hasHeader($name): bool
    {
        if ($this->headers === null) {
            throw new RequestException("Missing The HTTP Headers instance", 1);
        }
        return $this->headers->hasHeader($name);
    }

    /**
     * Get header value line
     * @param  string $name name/key (case-insensitive)
     * @return string
     */
    public function getHeaderLine($name): string
    {
        $data = $this->getHeaderLineData($name);
        /*
        if (count($data) === 0) {
            throw new RequestException("Could not find the header line", 1);
        }
         */
        return implode("; ", $data);
    }

    /**
     * Get header value data items
     * @param  string $name name/key (case insensitive)
     * @return array
     */
    public function getHeaderLineData(string $name): array
    {
        $this->headerLine = [];
        if ($this->hasHeader($name)) {
            $headerArr = $this->getHeader($name);
            foreach ($headerArr as $key => $val) {
                if (is_numeric($key)) {
                    $this->headerLine[] = $val;
                } else {
                    $this->headerLine[] = "{$key} {$val}";
                }
            }
        }
        return $this->headerLine;
    }

    /**
     * Set multiple headers
     * @param  array  $arr
     * @return static
     */
    public function withHeaders(array $arr): self
    {
        $inst = clone $this;
        foreach ($arr as $key => $val) {
            $inst = $inst->withHeader($key, $val);
        }
        return $inst;
    }

    /**
     * Set new header
     * @param  string $name
     * @param  mixed $value
     * @return static
     */
    public function withHeader(string $name, mixed $value): self
    {
        $inst = clone $this;
        $inst->headers->setHeader($name, $value);
        return $inst;
    }

    /**
     * Add header line
     * @param  string $name
     * @param  mixed $value
     * @return static
     */
    public function withAddedHeader(string $name, mixed $value): self
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
    public function withoutHeader($name): self
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
    public function withBody(StreamInterface $body): MessageInterface
    {
        $inst = clone $this;
        $inst->body = $body;
        return $inst;
    }
}
