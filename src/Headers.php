<?php

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\HeadersInterface;

class Headers implements HeadersInterface
{
    protected $headers = [];

    private static $getGlobalHeaders;

    public function __construct(array $headers = [])
    {
        $this->setHeaders($headers);
    }

    /**
     * Set new header
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function setHeader(string $name, mixed $value): void
    {
        $name = $this->normalizeKey($name);
        $this->headers[$name] = is_array($value) ? $value : array_map('trim', explode(';', $value));
    }

    /**
     * Set new headers
     * @param  array $arr
     * @return void
     */
    public function setHeaders(array $arr): void
    {
        foreach ($arr as $key => $val) {
            $this->setHeader($key, $val);
        }
    }

    /**
     * Check is a header exists
     * @param  string  $name Header name/key (case-insensitive)
     * @return boolean
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeKey($name);
        return (bool)($this->headers[$name] ?? null);
    }

    /**
     * Get all current headers
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get header from name/key
     * @param  string $name name/key (case insensitive)
     * @return array
     */
    public function getHeader($name): array
    {
        $name = $this->normalizeKey($name);
        $value = ($this->headers[$name] ?? []);
        return (is_array($value) ? $value : [$value]);
    }

    /**
     * Delete header from name/key
     * @param  string $name name/key (case-insensitive)
     * @return bool
     */
    public function deleteHeader(string $name): bool
    {
        if ($this->hasHeader($name)) {
            $name = $this->normalizeKey($name);
            unset($this->headers[$name]);
            return true;
        }
        return false;
    }

    /**
     * Used to make header keys consistent
     * @param  string $key
     * @return string
     */
    public function normalizeKey(string $key, bool $preserveCase = false): string
    {
        $key = strtr($key, '_', '-');
        if (!$preserveCase) {
            $key = strtolower($key);
        }
        return strtolower($key);
    }

    /**
     * Get global headers
     * @return array
     */
    final public static function getGlobalHeaders($skip = false): array
    {
        //if(static::$getGlobalHeaders === null) {
        if (!$skip && function_exists("getallheaders")) {
            static::$getGlobalHeaders = getallheaders();
        } else {
            static::$getGlobalHeaders = [];
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) <> 'HTTP_') {
                    continue;
                }
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                static::$getGlobalHeaders[$header] = $value;
            }
        }
        static::$getGlobalHeaders = array_change_key_case(static::$getGlobalHeaders);
        //}

        return static::$getGlobalHeaders;
    }
}
