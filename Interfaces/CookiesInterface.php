<?php

namespace PHPFuse\Http\Interfaces;

interface CookiesInterface
{
    public function set(string $name, string $value, int $expires, bool $force = false): void;
    public function has(string $name): bool;
    public function get(string $name, ?string $default = null): ?string;
    public function delete(string $name): void;
}
