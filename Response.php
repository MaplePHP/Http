<?php 

declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\StreamInterface;

class Response extends Message implements ResponseInterface
{

    const PHRASE = array(
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
    );

    private $statusCode = 200;
    private $phrase;

    private $contentType = "text/html";
    private $charset = "UTF-8";
    
    private $headerLines = array();
    private $location;

    function __construct(StreamInterface $body, array $headers = array(), int $status = 200, ?string $phrase = NULL, ?string $version = NULL) 
    {
        parent::__construct($body);
        $this->statusCode = $status;
        $this->headers = $this->setHeaders($headers);
        $this->body = $body;
        if(!is_null($version)) $this->version = $version;
        if(!is_null($phrase)) $this->phrase = $phrase;
    }

    public function getStatusCode() 
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->phrase = ($reasonPhrase ? $reasonPhrase : $clone->getReasonPhrase());
        return $clone;
    }

    public function getReasonPhrase() 
    {
        if(is_null($this->phrase)) {
            $this->phrase = $this::PHRASE[$this->statusCode];
        }
        return $this->phrase;
    }
    
    public function createHeaders() 
    {
        /*
        print_r($this->getHeaders());
        die();
         */
        foreach($this->getHeaders() as $key => $val) {
            $value = $this->getHeaderLine($key);
            header("{$key}: {$value}");
        }
    }
    
    public function location(string $url, int $statusCode = 302): void {
        if($statusCode !== 301 && $statusCode !== 302) throw new \Exception("The second argumnet (statusCode) is expecting 301 or 302", 1);

        $this->withStatus($statusCode)
        ->withHeader("Location", $url)
        ->createHeaders();
        
        die("Redirecting...");
    }

    public function redirect(string $url, int $statusCode = 302): void {
        $this->location($url, $statusCode);
    }

}
