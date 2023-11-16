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

    /**
     * Get root dir
     * @param  string $path
     * @return string
     */
    public function getDir(string $path = ""): string
    {
        return $this->dir . $path;
    }

    /**
     * Get root dir
     * @param  string $path
     * @return string
     */
    public function getRoot(string $path = ""): string
    {
        return $this->getDir($path);
    }

    /**
     * Get resource dir
     * @param  string $path
     * @return string
     */
    public function getResources(string $path = ""): string
    {
        return $this->getDir("resources/{$path}");
    }

    /**
     * Get resource dir
     * @param  string $path
     * @return string
     */
    public function getPublic(string $path = ""): string
    {
        return $this->getDir("public/{$path}");
    }

    /**
     * Get storage dir
     * @param  string $path
     * @return string
     */
    public function getStorage(string $path = ""): string
    {
        return $this->getDir("storage/{$path}");
    }

    /**
     * Get log dir
     * @param  string $path
     * @return string
     */
    public function getLogs(string $path = ""): string
    {
        return $this->getStorage("logs/{$path}");
    }

    /**
     * Get cache dir
     * @param  string $path
     * @return string
     */
    public function getCaches(string $path = ""): string
    {
        return $this->getStorage("caches/{$path}");
    }
}
