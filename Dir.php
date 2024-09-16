<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\DirInterface;
use MaplePHP\Http\Interfaces\DirHandlerInterface;

class Dir implements DirInterface
{
    private $dir;
    private $getRootDir;
    private $handler;
    

    public function __construct($dir, ?string $getRootDir = null)
    {
        $this->dir = $dir;
        $this->getRootDir = $getRootDir;
    }

    /**
     * Not required but recommended. You can pass on Directory shortcuts to the class
     * E.g. getPublic, getCss
     * @param DirHandlerInterface $handler
     */
    public function setHandler(DirHandlerInterface $handler): void
    {
        $this->handler = $handler;
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
        return $this->getRootDir.$path;
    }

    /**
     * Get log dir
     * @param  string $path
     * @return string
     */
    public function getLogs(string $path = ""): string
    {
        if(!is_null($this->handler)) {
            return $this->handler->getLogs($path);
        }
        return $this->getRoot("storage/logs/" . $path);
    }

    /**
     * Access handler
     * @param  string $method
     * @param  array $args
     * @return mixed
     * @psalm-taint-sink
     */
    public function __call($method, $args): mixed
    {
        if (!is_null($this->handler) && method_exists($this->handler, $method)) {
            return call_user_func_array([$this->handler, $method], $args);
        } else {
            throw new \BadMethodCallException("The method ({$method}) does not exist in \"".__CLASS__."\" (DirInterface or DirHandlerInterface).", 1);
        }
    }
}
