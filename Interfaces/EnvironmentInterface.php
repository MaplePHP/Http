<?php

namespace PHPFuse\Http\Interfaces;

interface EnvironmentInterface
{
    /**
     * Get request/server environment data
     * @param  string $key     Server key
     * @param  string $default Default value, returned if Env data is empty
     * @return string|null
     */
    public function get(string $key, ?string $default = ""): ?string;

    /**
     * Check if environment data exists
     * @param  string  $key Server key
     * @return boolean
     */
    public function has($key): bool;

    /**
     * Return all env
     * @return array
     */
    public function fetch(): array;

    /**
     * Get URI enviment Part data that will be passed to UriInterface and match to public object if exists.
     * @return array
     */
    public function getUriParts(array $add): array;
}
