<?php

namespace MaplePHP\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use MaplePHP\Http\Interfaces\HeadersInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseFactory implements ResponseFactoryInterface
{

    private ?HeadersInterface $headers = null;
    private ?StreamInterface $stream;

    /**
     * @param StreamInterface|null $body Optional body (if not set, empty stream will be used)
     * @param HeadersInterface|null $headers Optional headers object
     */
    public function __construct(?StreamInterface $stream = null, ?HeadersInterface $headers = null)
    {
        $this->stream = ($stream === null) ? new Stream(Stream::TEMP) : $stream;
        $this->headers = $headers;
    }

    /**
     * Create a new response instance.
     *
     * @param int $code HTTP status code
     * @param string|null $reasonPhrase Optional reason phrase
     * @param string|null $version HTTP version (e.g., '1.1')
     * @return ResponseInterface
     */
    public function createResponse(
        int $code = 200,
        ?string $reasonPhrase = null,
        ?string $version = null
    ): ResponseInterface {
        return new Response($this->stream, $this->headers, $code, $reasonPhrase, $version);
    }
}
