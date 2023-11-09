<?php

namespace PHPFuse\Http\Interfaces;

interface UrlInterface
{
    public function withType(null|string|array $type): self;
    public function getRealPath(): string;
    public function getDirPath(): string;
    public function getVars(): array;
    //public function filterParts($vars): array;
}
