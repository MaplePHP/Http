<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\MessageInterface;
use PHPFuse\Http\Interfaces\StreamInterface;

abstract class Message implements MessageInterface
{

    protected $server;
    protected $serverProtocol;
    protected $version;
    protected $headers = array();
    protected $body;

    function __construct(?StreamInterface $body = NULL) 
    {
        $this->server = $_SERVER;
        $this->serverProtocol = ($this->server['SERVER_PROTOCOL'] ?? "HTTP/1.1");
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
        return $inst->body;
    }

}
