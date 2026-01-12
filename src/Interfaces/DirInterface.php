<?php

namespace MaplePHP\Http\Interfaces;

interface DirInterface
{
    /**
     * Get root dir
     * @param  string $path
     * @return string
     */
    public function getDir(string $path = ""): string;

    /**
     * Get root dir
     * @param  string $path
     * @return string
     */
    public function getRoot(string $path = ""): string;

    /**
     * Not required but recommended. You can pass on Directory shortcuts to the class
     * E.g. getPublic, getCss
     * @param DirHandlerInterface $handler
     */
    public function setHandler(DirHandlerInterface $handler): void;

    /**
     * Get log dir
     * @param  string $path
     * @return string
     */
    public function getLogs(string $path = ""): string;
}
