<?php

declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\CookiesInterface;

class Cookies implements CookiesInterface
{
    //private $name;
    //private $value;
    //private $expires;
    private $path;
    private $domain;
    private $secure;
    private $httpOnly;
    private $samesite;

    /**
     * Set Cookie
     * @param string       $path
     * @param string       $domain
     * @param bool|boolean $secure
     * @param bool|boolean $httpOnly
     */
    public function __construct(
        string $path = "/",
        string $domain = "",
        bool $secure = true,
        bool $httpOnly = true
    ) {
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
    }
    
    /**
     * Set cookie allowed path
     * @param string $path URI Path
     * @return self
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set cookie allowed domain
     * @param string $domain URI Path
     * @return self
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Set cookie secure flag (HTTPS only: true)
     * @param bool $secure URI Path true/false
     * @return self
     */
    public function setSecure(bool $secure): self
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Set cookie http only flag. Cookie won't be accessible by scripting languages, such as JavaScript if true.
     * Can effectively help to reduce identity theft through XSS attacks, Not supported in all browsers tho
     * @param bool $httpOnly enable http only flag
     * @return self
     */
    public function sethttpOnly(bool $httpOnly): self
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }


    /**
     * Set same site
     * @param string $samesite
     * @return self
     */
    public function setSameSite(string $samesite): self
    {
        $samesite = ucfirst(strtolower($samesite));
        if ($samesite !== "None" && $samesite !== "Lax" && $samesite !== "Strict") {
            throw new \InvalidArgumentException("The argument needs to be one of (None, Lax or Strict)", 1);
        }
        $this->samesite = $samesite;
        return $this;
    }

    /**
     * Set cookie
     * @param string $name
     * @param mixed $value
     * @param int   $expires
     * @return void
     */
    public function set(string $name, string $value, int $expires, bool $force = false): void
    {
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            setcookie($name, $value, $this->cookieOpt($expires));
        } else {
            setcookie($name, $value, $expires, $this->path, $this->domain, $this->secure, $this->httpOnly);
        }
        if ($force) {
            $_COOKIE[$name] = $value;
        }
    }

    /**
     * Check is cookie exists
     * @param  string  $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return (isset($_COOKIE[$name]));
    }

    /**
     * Get cookie
     * @param  string      $name
     * @param  string|null $default
     * @return string|null
     */
    public function get(string $name, ?string $default = null): ?string
    {
        return ($_COOKIE[$name] ?? $default);
    }

    /**
     * Delete Cookie
     * @param  string $name
     * @return void
     */
    public function delete(string $name): void
    {
        if ($this->has($name)) {
            $this->set($name, "", time());
            unset($_COOKIE[$name]);
        }
    }

    /**
     * Check if cookies settings in this instance has great enough security to save e.g. CSRF token.
     * Can not be read or set in: frontend, cross domain or in http (only https)
     * @return bool
     */
    public function isSecure(): bool
    {
        return ($this->samesite === "Strict" && $this->secure && $this->httpOnly);
    }

    /**
     * Set cookie options
     * @param  int $expires
     * @return array
     */
    private function cookieOpt(int $expires): array
    {
        return [
            'expires' => $expires,
            'path' => $this->path,
            'domain' => $this->domain,
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->samesite
        ];
    }
}
