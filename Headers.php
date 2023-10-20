<?php

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\HeadersInterface;


class Headers implements HeadersInterface
{

    protected $headers = array();

    private static $getGlobalHeaders;
    
    function __construct(array $headers = []) 
    {
        $this->setHeaders($headers);
    }

    /**
     * Set new header
     * @param  string $name 
     * @param  string/array $value
     * @return void
     */
    public function setHeader($name, $value): void 
    {
        $name = $this->normalizeKey($name);
        $this->headers[$name] = is_array($value) ? $value : array_map('trim', explode(';', $value));
    }

    /**
     * Set new headers
     * @param  string $name 
     * @param  string/array $value
     * @return void
     */
    public function setHeaders(array $arr): void 
    {
        foreach($arr as $key => $val) $this->setHeader($key, $val);
    }

    /**
     * Check is a header exists 
     * @param  string  $name Header name/key (case insensitive)
     * @return boolean
     */
    public function hasHeader($name): bool 
    {
        $name = $this->normalizeKey($name);
        return (bool)($this->headers[$name] ?? NULL);
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
     * Used to make header keys consistent 
     * @param  string $key
     * @return string
     */
    public function normalizeKey(string $key, bool $preserveCase = false): string 
    {
        $key = strtr($key, '_', '-');
        if(!$preserveCase) $key = strtolower($key);
        return strtolower($key);
    }

    /**
     * Get global headers
     * @return array
     */
    final public static function getGlobalHeaders($skip = false): array 
    {
        //if(is_null(static::$getGlobalHeaders)) {
            if(!$skip && function_exists("getallheaders")) {
                static::$getGlobalHeaders = getallheaders();
            } else {
                static::$getGlobalHeaders = array();
                foreach($_SERVER as $key => $value) {
                    if(substr($key, 0, 5) <> 'HTTP_') continue;
                    $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    static::$getGlobalHeaders[$header] = $value;
                }
            }
            static::$getGlobalHeaders = array_change_key_case(static::$getGlobalHeaders);
        //}
        
        return static::$getGlobalHeaders;
    }


}
