<?php

namespace PHPFuse\Http\Interfaces;

interface HeadersInterface
{
    /**
     * Set new header
     * @param  string $name
     * @param  mixed $value
     * @return void
     */
    public function setHeader(string $name, mixed $value): void;

    /**
     * Set new headers
     * @param  array $arr
     * @return void
     */
    public function setHeaders(array $arr): void;

    /**
     * Check is a header exists
     * @param  string  $name Header name/key (case insensitive)
     * @return boolean
     */
    public function hasHeader($name): bool;

    /**
     * Get all current headers
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get header from name/key
     * @param  string $name name/key (case insensitive)
     * @return array
     */
    public function getHeader($name): array|string;
}
