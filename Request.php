<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\UriInterface;
use PHPFuse\Http\Interfaces\HeadersInterface;
use PHPFuse\Http\Interfaces\StreamInterface;
use PHPFuse\Http\Uri;

class Request extends Message implements RequestInterface
{
    private $method;
    private $uri;
    private $requestTarget;
    protected $headers;
    protected $body;

    public function __construct(
        string $method,
        UriInterface|string $uri,
        HeadersInterface|array $headers = [],
        StreamInterface|array|string|null $body = null
    ) {
        $this->method = $method; // WHITELIST CASE SENSITIVE UPPERCASE
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->headers = is_array($headers) ? new Headers($headers) : $headers;
        $this->body = $this->resolveRequestStream($body);
        $this->setHostHeader();
    }

    /**
     * Get the message request target (path+query)
     * @return string
     */
    public function getRequestTarget(): string
    {
        $this->requestTarget = $this->getUri()->getPath();
        if ($query = $this->getUri()->getQuery()) {
            $this->requestTarget .= '?' . $query;
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
        return $this->uri;
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
        if ($preserveHost) {
            $uri = $uri->withHost($this->getHeader("Host"));
        }
        $inst->uri = $uri;
        return $inst;
    }

    /**
     * Chech if is request is SSL
     * @return boolean [description]
     */
    public function isSSL(): bool
    {
        $https = strtolower($this->env->get("HTTPS"));
        return (bool)($https === "on" || $https === "1" || $this->getPort() === 443);
    }

    /**
     * Get Server request port
     * @return int
     */
    public function getPort(): int
    {
        $serverPort = $this->env->get("SERVER_PORT");
        $port = (int)(($serverPort) ? $serverPort : $this->uri->getPort());
        return (int)$port;
    }

    /**
     * Set host header if missing or overwrite if custom is set.
     * @return void
     */
    final protected function setHostHeader(): void
    {
        if (!$this->headers->hasHeader('Host') || $this->uri->getHost() !== '') {
            $this->headers->setHeader('Host', $this->uri->getHost());
        }
    }

    /**
     * This will resolve the Request Stream and make the call user friendly
     * @param  StreamInterface|array|string|null $body
     * @return StreamInterface
     */
    private function resolveRequestStream(StreamInterface|array|string|null $body): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            $stream = $body;
        } else {
            if (is_array($body)) {
                $body = http_build_query($body);
            }
            $stream = new Stream(Stream::TEMP);
            if (!is_null($body)) {
                $stream->write($body);
                $stream->rewind();
            }
        }
        return $stream;
    }
}
