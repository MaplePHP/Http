<?php


declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\DirInterface;

class Dir implements DirInterface
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function getDir(string $path = "")
    {
        return $this->dir.$path;
    }

    public function getRoot(string $path = "")
    {
        return $this->getDir($path);
    }

    public function getResources(string $path = "")
    {
        return $this->getDir("resources/{$path}");
    }

    public function getPublic(string $path = "")
    {
        return $this->getDir("public/{$path}");
    }

    public function getStorage(string $path = "")
    {
        return $this->getDir("storage/{$path}");
    }

    public function getLogs(string $path = "")
    {
        return $this->getStorage("logs/{$path}");
    }

    public function getCaches(string $path = "")
    {
        return $this->getStorage("caches/{$path}");
    }
}
