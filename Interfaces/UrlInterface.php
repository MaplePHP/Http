<?php

namespace MaplePHP\Http\Interfaces;

interface UrlInterface
{
    /**
     * Access http PSR URI message
     * @return UriInterface
     */
    public function getUri(): UriInterface;

    /**
     * With URI path type key
     * @param  null|string|array  $type
     * @return static
     */
    public function withType(null|string|array $type): self;

    /**
     * Same as withType except that you Need to select a part
     * @param  string|array  $type
     * @return static
     */
    public function select(string|array $type): self;

    /**
     * Same as withType except it will only reset
     * @return static
     */
    public function reset(): self;

    /**
     * Add to URI path
     * @param array|string $arr
     * @return static
     */
    public function add(array|string $arr): self;

    /**
     * Get real htaccess path (possible directories filtered out)
     * @return string
     */
    public function getRealPath(): string;

    /**
     * Extract and get directories from the simulated htaccess path
     * @return string
     */
    public function getDirPath(): string;

    /**
     * Get vars/path as array
     * @return array
     */
    public function getVars(): array;

    /**
     * Get vars/path as array
     * @return array
     */
    public function getParts(): array;


    /**
     * Get expected slug from path
     * @return string
     */
    public function get(): string;

    /**
     * Get expected slug from path
     * @return string
     */
    public function current(): string;

    /**
     * Get first path item
     * @return string
     */
    public function first(): string;

    /**
     * Get travers to prev path item
     * @return string
     */
    public function prev(): string;

    /**
     * Get travers to next path item
     * @return string
     */
    public function next(): string;

    /**
     * Get last path item
     * @return string
     */
    public function last(): string;

    /**
     * Get root URL
     * @param  string   $path       add to URI
     * @param  bool     $endSlash   add slash to the end of root URL (default false)
     * @return string
     */
    public function getRoot(string $path = "", bool $endSlash = false): string;

    /**
     * Get root URL DIR
     * @param  string   $path       add to URI
     * @param  bool     $endSlash   add slash to the end of root URL (default false)
     * @return string
     */
    public function getRootDir(string $path = "", bool $endSlash = false): string;

    /**
     * Get full URL (path is changeable with @add and @withType method)
     * @param  string $addToPath add to URI
     * @return string
     */
    public function getUrl(string $addToPath = ""): string;

    /**
     * Not required but recommended. You can pass on URL shortcuts to the class
     * E.g. getPublic, getCss
     * @param UrlHandlerInterface $handler
     */
    public function setHandler(UrlHandlerInterface $handler): void;
}
