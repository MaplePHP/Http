<?php


declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\CookiesInterface;

class Cookies implements CookiesInterface
{
    private $name;
    private $value;
    private $expires;
    private $path;
    private $domain;
    private $secure;
    private $httponly;
    private $samesite;

    /**
     * [__construct description]
     *  setcookie(
        string $name,
        string $value = "",
        int $expires_or_options = 0,
        string $path = "",
        string $domain = "",
        bool $secure = false,
        bool $httponly = false
    ): bool
     * @param [type] $uri [description]
     */
    public function __construct(
        string $path = "/",
        string $domain = "",
        bool $secure = true,
        bool $httponly = true
    ) {
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httponly = $httponly;
    }

    /**
     * Set cookie allowed path
     * @param string $path URI Path
     */
    public function setPath(string $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Set cookie allowed domain
     * @param string $path URI Path
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Set cookie secure flag (HTTPS only: true)
     * @param string $path URI Path
     */
    public function setSecure(bool $secure)
    {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Set cookie http only flag. Cookie won't be accessible by scripting languages, such as JavaScript if true.
     * Can effectively help to reduce identity theft through XSS attacks, Not supported in all browsers tho
     * @param string $path URI Path
     */
    public function setHttpOnly(bool $httponly)
    {
        $this->httponly = $httponly;
        return $this;
    }


    /**
     * Set same site
     * (Requires PHP version >= 7.3.0)
     * @param string $sameSite [description]
     */
    public function setSameSite(string $samesite)
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
     */
    public function set(string $name, string $value, int $expires, bool $force = false): void
    {
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            setcookie($name, $value, $this->cookieOpt($expires));
        } else {
            setcookie($name, $value, $expires, $this->path, $this->domain, $this->secure, $this->httponly);
        }
        if ($force) {
            $_COOKIE[$name] = $value;
        }
    }

    /**
     * Check is cookie exists
     * @param  string  $name
     * @return boolean
     */
    public function has(string $name): bool
    {
        return (bool)(isset($_COOKIE[$name]));
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
     * @return boolean
     */
    public function isSecure(): bool
    {
        return (bool)($this->samesite === "Strict" && $this->secure && $this->httponly);
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
            'httponly' => $this->httponly,
            'samesite' => $this->samesite
        ];
    }
}
