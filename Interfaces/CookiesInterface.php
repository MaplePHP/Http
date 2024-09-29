<?php

namespace MaplePHP\Http\Interfaces;

interface CookiesInterface
{
    /**
     * Set cookie allowed path
     * @param string $path URI Path
     * @return self
     */
    public function setPath(string $path): self;

    /**
     * Set cookie allowed domain
     * @param string $domain URI Path
     * @return self
     */
    public function setDomain(string $domain): self;

    /**
     * Set cookie secure flag (HTTPS only: true)
     * @param bool $secure URI Path true/false
     * @return self
     */
    public function setSecure(bool $secure): self;

    /**
     * Set cookie http only flag. Cookie won't be accessible by scripting languages, such as JavaScript if true.
     * Can effectively help to reduce identity theft through XSS attacks, Not supported in all browsers tho
     * @param bool $httpOnly enable http only flag
     * @return self
     */
    public function sethttpOnly(bool $httpOnly): self;

    /**
     * Set same site
     * @param string $samesite
     * @return self
     */
    public function setSameSite(string $samesite): self;

    /**
     * Set cookie
     * @param string $name
     * @param mixed $value
     * @param int   $expires
     * @return void
     */
    public function set(string $name, string $value, int $expires, bool $force = false): void;

    /**
     * Check is cookie exists
     * @param  string  $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
    * Get cookie
    * @param  string      $name
    * @param  string|null $default
    * @return string|null
    */
    public function get(string $name, ?string $default = null): ?string;

    /**
     * Delete Cookie
     * @param  string $name
     * @return void
     */
    public function delete(string $name): void;

    /**
     * Check if cookies settings in this instance has great enough security to save e.g. CSRF token.
     * Can not be read or set in: frontend, cross domain or in http (only https)
     * @return bool
     */
    public function isSecure(): bool;
}
