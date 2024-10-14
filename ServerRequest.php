<?php

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\ServerRequestInterface;
use MaplePHP\Http\Interfaces\UriInterface;
use MaplePHP\Http\Interfaces\EnvironmentInterface;
use MaplePHP\Http\Stream;

class ServerRequest extends Request implements ServerRequestInterface
{
    protected $attr = [];
    protected $env;
    protected $queryParams;
    protected $parsedBody;

    public function __construct(UriInterface $uri, EnvironmentInterface $env)
    {
        $this->env = $env;

        parent::__construct(
            $this->env->get("REQUEST_METHOD", "GET"),
            $uri,
            new Headers(Headers::getGlobalHeaders()),
            new Stream(Stream::INPUT)
        );

        $this->attr = [
            "env" => $this->env->fetch(),
            "cookies" => $_COOKIE,
            "files" => $this->normalizeFiles($_FILES)
        ];
    }


    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->env->fetch();
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->attr['cookies'];
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): self
    {
        $inst = clone $this;
        $inst->attr['cookies'] = $cookies;
        return $inst;
    }


    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        if (is_null($this->queryParams)) {
            parse_str($this->getUri()->getQuery(), $this->queryParams);
        }
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query): self
    {
        $inst = clone $this;
        $inst->queryParams = $query;
        return $inst;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array
    {
        return $this->attr['files'];
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $inst = clone $this;
        $inst->attr['files'] = $uploadedFiles;
        return $inst;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody(): null|array|object
    {
        if (is_null($this->parsedBody) && $this->getMethod() === "POST") {
            $header = $this->getHeader('Content-Type');
            $contents = (string)$this->getBody();
            switch (($header[0] ?? null)) {
                case "application/x-www-form-urlencoded":
                    parse_str($contents, $this->parsedBody);
                    break;
                case "multipart/form-data":
                    $this->parsedBody = $_POST;
                    break;
                case "application/json":
                    $this->parsedBody = json_decode($contents, true);
                    break;
                case "application/xml":
                    $this->parsedBody = simplexml_load_string($contents);
                    break;
            }
        }
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data): self
    {
        $inst = clone $this;
        $inst->parsedBody = $data;
        return $inst;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attr;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null): mixed
    {
        return ($this->attr[$name] ?? $default);
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): self
    {
        $inst = clone $this;
        $inst->attr[$name] = $value;
        return $inst;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name): self
    {
        $inst = clone $this;
        unset($inst->attr[$name]);
        return $inst;
    }

    /**
     * This will normalize/flatten the a file Array
     * @param  array  $file
     * @return array
     */
    protected function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['error'])) {
                $normalized[$key] = $this->normalizeFileArray($file);
            } else {
                $normalized[$key] = new UploadedFile($file);
            }
        }
        return $normalized;
    }

    /**
     * This will normalize/flatten the a multi-level file Array
     * @param  array  $file
     * @return array
     */
    protected function normalizeFileArray(array $file): array
    {
        $normalized = [];

        foreach ($file['error'] as $key => $error) {
            if (is_array($error)) {
                $normalized[$key] = $this->normalizeFileArray([
                    'name'     => $file['name'][$key],
                    'type'     => $file['type'][$key],
                    'tmp_name' => $file['tmp_name'][$key],
                    'error'    => $file['error'][$key],
                    'size'     => $file['size'][$key]
                ]);
            } else {
                $normalized[$key] = new UploadedFile(
                    $file['name'][$key],
                    $file['type'][$key],
                    $file['tmp_name'][$key],
                    $file['error'][$key],
                    $file['size'][$key]
                );
            }
        }

        return $normalized;
    }


    /*
    public function getEnv() {
        return $this->env;
    }
     */
}
