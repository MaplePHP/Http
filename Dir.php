<?php 

declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\DirInterface;

class Dir implements DirInterface
{

    private $dir;
    
    function __construct($dir)
    {
        $this->dir = $dir;
    }

    function getDir(string $path = "") {
        return $this->dir.$path;
    }

    function getRoot(string $path = "") {
        return $this->getDir($path);
    }

    function getResources(string $path = "") {
        return $this->getDir("resources/{$path}");
    }

    function getPublic(string $path = "") {
        return $this->getDir("public/{$path}");
    }

    function getStorage(string $path = "") {
        return $this->getDir("storage/{$path}");
    }

    function getLogs(string $path = "") {
        return $this->getStorage("logs/{$path}");
    }

    function getCaches(string $path = "") {
        return $this->getStorage("caches/{$path}");
    }

}
