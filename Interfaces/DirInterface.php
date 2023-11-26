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
     * Get resource dir
     * @param  string $path
     * @return string
     */
    public function getResources(string $path = ""): string;

    /**
     * Get resource dir
     * @param  string $path
     * @return string
     */
    public function getPublic(string $path = ""): string;

    /**
     * Get storage dir
     * @param  string $path
     * @return string
     */
    public function getStorage(string $path = ""): string;

    /**
     * Get log dir
     * @param  string $path
     * @return string
     */
    public function getLogs(string $path = ""): string;


    /**
     * Get cache dir
     * @param  string $path
     * @return string
     */
    public function getCaches(string $path = ""): string;
}
