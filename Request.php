<?php

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\RequestInterface;
use MaplePHP\Http\Interfaces\UriInterface;
use MaplePHP\Http\Interfaces\HeadersInterface;
use MaplePHP\Http\Interfaces\StreamInterface;

class Request extends Message implements RequestInterface
{
    private $method;
    private $uri;
    private $requestTarget;
    protected $headers;
    protected $body;
    protected $cliKeywords;
    protected $cliArgs;

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
        if ($this->env === null) {
            $this->env = new Environment();
        }
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
     * @param  mixed $requestTarget
     * @return static
     */
    public function withRequestTarget(mixed $requestTarget): RequestInterface
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
     * @param  string $method
     * @return static
     */
    public function withMethod(string $method): RequestInterface
    {
        $inst = clone $this;
        $inst->method = strtoupper($method);
        return $inst;
    }

    /**
     * Get URI instance with set request message
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
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $inst = clone $this;
        if ($preserveHost) {
            $uri = $uri->withHost($this->getHeaderLine("Host"));
        }
        $inst->uri = $uri;
        return $inst;
    }

    /**
     * Check if is request is SSL
     * @return bool
     */
    public function isSSL(): bool
    {
        $https = strtolower($this->env->get("HTTPS"));
        return ($https === "on" || $https === "1" || $this->getPort() === 443);
    }

    /**
     * Get Server request port
     * @return int
     */
    public function getPort(): int
    {
        $serverPort = $this->env->get("SERVER_PORT");
        return (int)(((int)$serverPort > 0) ? $serverPort : $this->uri->getPort());
    }

    /**
     * Set the host header if missing or overwrite if custom is set.
     * @return void
     */
    final protected function setHostHeader(): void
    {
        if (!$this->headers->hasHeader('Host') || $this->uri->getHost() !== '') {
            $this->headers->setHeader('Host', $this->uri->getHost());
        }
    }

    /**
     * This will resolve the Request Stream and make the call user-friendly
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
            if ($body !== null) {
                $stream->write($body);
                $stream->rewind();
            }
        }
        return $stream;
    }

    /**
    * Get Cli keyword
    * @return string|null
    */
    public function getCliKeyword(): ?string
    {
        if ($this->cliKeywords === null) {
            $new = [];
            $arg = $this->getUri()->getArgv();
            foreach ($arg as $val) {
                if (is_string($val)) {
                    if ((str_starts_with($val, "--")) || (str_starts_with($val, "-"))) {
                        break;
                    } else {
                        $new[] = $val;
                    }
                }
            }
            array_shift($new);
            $this->cliKeywords = implode("/", $new);
        }

        return $this->cliKeywords;
    }

    /**
     * Get Cli arguments
     * @return array
     */
    public function getCliArgs(): array
    {
        if ($this->cliArgs === null) {
            $args = $this->getUri()->getArgv();
            $this->cliArgs = [];
            foreach ($args as $arg) {
                if (is_string($arg)) {
                    $arg = str_replace("&", "#", $arg);
                    if ((($pos1 = strpos($arg, "--")) === 0) || (str_starts_with($arg, "-"))) {
                        parse_str(substr($arg, ($pos1 !== false ? 2 : 1)), $result);
                        foreach ($result as &$val) {
                            $val = str_replace("#", "&", $val);
                        }
                        $this->cliArgs = array_merge($this->cliArgs, $result);
                    }
                }
            }
        }

        return $this->cliArgs;
    }
}
