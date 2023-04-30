<?php

namespace PHPFuse\Http\Interfaces;


interface CookiesInterface
{
    function set(string $name, string $value, int $expires, bool $force = false): void;
    function has(string $name): bool;
    function get(string $name, ?string $default = NULL): ?string;
    function delete(string $name): void;
}

