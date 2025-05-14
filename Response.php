<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\ResponseInterface;
use MaplePHP\Http\Interfaces\StreamInterface;
use MaplePHP\Http\Interfaces\HeadersInterface;

class Response extends Message implements ResponseInterface
{
    public const PHRASE = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    private $statusCode = 200;
    private $phrase;
    private $description; // Can be used to describe status code
    private $modDate;
    private $hasHeadersInit;

    public function __construct(
        StreamInterface $body,
        ?HeadersInterface $headers = null,
        int $status = 200,
        ?string $phrase = null,
        ?string $version = null
    ) {
        $this->body = $body;
        $this->statusCode = $status;
        $this->headers = $headers === null ? new Headers() : $headers;
        //$this->body = $body;
        if ($version !== null) {
            $this->version = $version;
        }
        if ($phrase !== null) {
            $this->phrase = $phrase;
        }
    }

    /**
     * Response with status code
     * @param  int    $code
     * @param  string $reasonPhrase
     * @return static
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->phrase = ($reasonPhrase ? $reasonPhrase : $clone->getReasonPhrase());
        return $clone;
    }

    /**
     * Get current response status code
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get current response status phrase
     * @return string
     */
    public function getReasonPhrase(): string
    {
        if ($this->phrase === null) {
            $this->phrase = ($this::PHRASE[$this->statusCode] ?? "");
        }
        return $this->phrase;
    }

    /**
     * Check is status response counts as a valid response
     * HTTP response status codes in the 200 range generally indicate a successful or valid response.
     * @return bool
     */
    public function isValidResponse(): bool
    {
        return ($this->statusCode >= 200 && $this->statusCode < 300);
    }

    /**
     * Get modified date
     * @return int|null
     */
    public function getModDate(): ?int
    {
        return $this->modDate;
    }

    /**
     * Clear cache on modified date (E.g. can be used with uodate date on post in DB)
     * @param  string $date
     * @return static
     */
    public function withLastModified(string $date): ResponseInterface
    {
        $clone = clone $this;
        $clone->modDate = strtotime($date);
        return $clone->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $clone->modDate) . ' GMT');
    }

    /**
     * Clear cache at given date (E.g. can be used if you set a publish date on a post in DB)
     * @param  string   $date
     * @return static
     */
    public function withExpires(string $date): ResponseInterface
    {
        return $this->withHeader("Expires", gmdate('D, d M Y H:i:s', strtotime($date)) . ' GMT');
    }

    /**
     * Set cache
     * @param int $time expect timestamp
     * @param int $ttl  ttl in seconds
     * @return static
     */
    public function setCache(int $time, int $ttl): ResponseInterface
    {
        return $this->withHeaders([
            "Cache-Control" => "max-age={$ttl}, immutable, public",
            "Expires" => date("D, d M Y H:i:s", $time + $ttl) . " GMT",
            "Pragma" => "public"
        ]);
    }

    /**
     * Clear cache. No exceptions!
     * Out of security reasons it is actually good practice to call this BY default on a framework
     * The reason for this is to make sure that sensitive data is not cached.
     * So then you as the developer can then make the choice to cache the data or not.
     * @return static
     */
    public function clearCache(): ResponseInterface
    {
        return $this->withHeaders([
            "Cache-Control" => "no-store, no-cache, must-revalidate, private",
            "Expires" => "Sat, 26 Jul 1997 05:00:00 GMT"
        ]);
    }



    /**
     * Redirect to new location
     * @param  string      $url        URL
     * @param  int|integer $statusCode 301 or 302
     * @return void
     */
    public function location(string $url, int $statusCode = 302): void
    {
        if ($statusCode !== 301 && $statusCode !== 302) {
            throw new \Exception("The second argument (statusCode) is expecting 301 or 302", 1);
        }
        $this->withStatus($statusCode)
        ->withHeader("Location", $url)
        ->createHeaders();
        die();
    }


    /**
     * Create headers createHeaders will only be executed once per instance
     * @return void
     */
    public function createHeaders(): void
    {
        if ($this->hasHeadersInit === null) {
            $this->hasHeadersInit = true;
            foreach ($this->getHeaders() as $key => $_unusedVal) {
                $value = $this->getHeaderLine($key);
                header("{$key}: {$value}");
            }
        }
    }

    /**
     * Will build with the createHeaders method then and execute all the headers
     * @return void
     */
    public function executeHeaders(): void
    {
        $this->createHeaders();
        $statusLine = sprintf(
            'HTTP/%s %s %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        header($statusLine, true, $this->getStatusCode());
    }


    /**
     * Set extra description, can be used to describe the error code more in details
     * @param string $description
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get current response status description
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }
}
