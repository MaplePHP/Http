<?php
/**
 * UrlHandlerInterface
 * Is used to extend upon the Url instance with more url methods
 */
namespace MaplePHP\Http\Interfaces;

interface UrlHandlerInterface
{
	/**
     * Get the public dir path
     * @return string|null
     */
    public function getPublicDirPath(): ?string;
}
