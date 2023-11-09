<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\EnvironmentInterface;
use PHPFuse\DTO\Format;

class Environment implements EnvironmentInterface
{
    private $path;
    private $env;

    public function __construct(array $env = [])
    {
        $this->env = array_merge(($_SERVER ?? []), $env);
    }

    /**
     * Get request/server environment data
     * @param  string $key     Server key
     * @param  string $default Default value, returned if Env data is empty
     * @return string|null
     */
    public function get(string $key, ?string $default = ""): ?string
    {
        $key = strtoupper($key);
        return ($this->env[$key] ?? $default);
    }

    /**
     * Check if environment data exists
     * @param  string  $key Server key
     * @return boolean
     */
    public function has($key): bool
    {
        return (bool)($this->get($key, null));
    }


    /**
     * Return all env
     * @return array
     */
    public function fetch(): array
    {
        return $this->env;
    }

    /**
     * Get URI enviment Part data that will be passed to UriInterface and match to public object if exists.
     * @return array
     */
    public function getUriParts(array $add): array
    {
        $arr = array();
        $arr['scheme'] = ($this->get("HTTPS") === 'on') ? 'https' : 'http';
        $arr['user'] = $this->get("PHP_AUTH_USER");
        $arr['pass'] = $this->get("PHP_AUTH_PW");
        $arr['host'] = ($host = $this->get("HTTP_HOST")) ? $host : $this->get("SERVER_NAME");
        $arr['port'] = $this->get("SERVER_PORT", null);
        $arr['path'] = $this->getPath();
        $arr['query'] = $this->get("QUERY_STRING");
        $arr['fragment'] = null;
        if (!is_null($arr['port'])) {
            $arr['port'] = (int)$arr['port'];
        }

        $arr = array_merge($arr, $add);
        return $arr;
    }

    /**
     * Build and return URI Path from environment
     * @return string
     */
    public function getPath(): string
    {
        if (is_null($this->path)) {
            $basePath = '';
            $requestName = Format\Str::value($this->get("SCRIPT_NAME"))->extractPath()->get();
            $requestDir = dirname($requestName);
            $requestUri = Format\Str::value($this->get("REQUEST_URI"))->extractPath()->get();

            $this->path = $requestUri;
            if (stripos($requestUri, $requestName) === 0) {
                $basePath = $requestName;
            } elseif ($requestDir !== '/' && stripos($requestUri, $requestDir) === 0) {
                $basePath = $requestDir;
            }
            if ($basePath) {
                $this->path = ltrim(substr($requestUri, strlen($basePath)), '/');
            }
        }
        return $this->path;
    }
}
