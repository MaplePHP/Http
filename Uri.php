<?php


declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\UriInterface;
use PHPFuse\DTO\Format;

class Uri implements UriInterface
{
    private const DEFAULT_PORTS = [
        'http'  => 80,
        'https' => 443,
        'ftp' => 21,
        'gopher' => 70,
        'nntp' => 119,
        'news' => 119,
        'telnet' => 23,
        'tn3270' => 23,
        'imap' => 143,
        'pop' => 110,
        'ldap' => 389,
    ];

    private $uri;
    private $parts = array();
    private $scheme;
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $query;
    private $fragment; // Anchor/after hash
    private $userInfo;
    private $authority;
    private $dir;
    private $argv;
    private $encoded;
    private $build;


    /**
     * URI in parts
     * @param array|string $uri
     */
    public function __construct(array|string $uri)
    {
        if (is_array($uri)) {
            $this->parts = $uri;
        } else {
            $this->parts = parse_url($uri);
        }
        $this->fillParts();
    }

    /**
     * Get formated URI
     * @return string
     */
    public function __toString()
    {
        return $this->getUri();
    }

    /**
     * Get schema
     * @return string (ex: http/https)
     */
    public function getScheme(): string
    {
        if ($val = $this->getUniquePart("scheme")) {
            $this->encoded['scheme'] = Format\Str::value($val)->tolower()->get();
        }
        return (string)$this->encoded['scheme'];
    }

    /**
     * Get dir
     * @return string (ex: http/https)
     */
    public function getDir(): string
    {
        if ($val = $this->getUniquePart("dir")) {
            $this->encoded['dir'] = $val;
        }
        return (string)$this->encoded['dir'];
    }

    /**
     * Get authority
     * @return string (ex: [userInfo@]host[:port])
     */
    public function getAuthority(): string
    {
        if (is_null($this->authority)) {
            $this->authority = "";

            if (($host = $this->getHost()) && ($userInfo = $this->getUserInfo())) {
                $this->authority = "{$userInfo}@{$host}";
            } else {
                $this->authority = $host;
            }
            if ($port = $this->getPort()) {
                $this->authority .= ":{$port}";
            }
        }

        return $this->authority;
    }

    /**
     * Get user info
     * @return string (ex: username:password)
     */
    public function getUserInfo(): string
    {
        if (is_null($this->userInfo)) {
            $this->userInfo = "";
            $user = $pass = null;
            if ($user = $this->getUniquePart("user")) {
                $this->encoded['user'] = $user;
            }
            if ($pass = $this->getUniquePart("pass")) {
                $this->encoded['pass'] = $pass;
            }

            if (!is_null($user)) {
                $this->userInfo .= "{$user}";
                if (!is_null($pass)) {
                    $this->userInfo .= ":{$pass}";
                }
            }
        }
        return $this->userInfo;
    }

    /**
     * Get host
     * @return string (ex: example.com / staging.example.com / 127.0.0.1 / localhost)
     */
    public function getHost(): string
    {
        if ($val = $this->getUniquePart("host")) {
            $this->encoded['host'] = Format\Str::value($val)->tolower()->get();
        }
        return (string)$this->encoded['host'];
    }


    /**
     * Get port
     * @return int|null (ex: 443)
     */
    public function getPort(): ?int
    {
        if ($val = $this->getUniquePart("port")) {
            $this->encoded['port'] = (int)$val;
        }
        return ($this->encoded['port'] ?? null);
    }

    /**
     * Get port
     * @return int|null (ex: 443)
     */
    public function getDefaultPort(): ?int
    {
        if (is_null($this->port) && !is_null($this->scheme)) {
            $this->port = ($this::DEFAULT_PORTS[$this->getScheme()] ?? null);
        }
        if ($val = $this->getUniquePart("port")) {
            $this->encoded['port'] = (int)$val;
        }
        return $this->port;
    }

    /**
     * Get path (Supports trailing slash)
     * @return string (ex: /about-us/workers)
     */
    public function getPath(): string
    {
        if ($val = $this->getUniquePart("path")) {
            $this->encoded['path'] = Format\Str::value($val)->toggleUrlencode(['%2F'], ['/'])->get();
        }
        return (string)$this->encoded['path'];
    }

    /**
     * Get query string
     * @return string (ex: page-id=12&filter=2)
     */
    public function getQuery(): string
    {
        if ($val = $this->getUniquePart("query")) {
            $this->encoded['query'] = Format\Str::value($val)
            ->toggleUrlencode(['%3D', '%26', '%5B', '%5D'], ['=', '&', '[', ']'])
            ->get();
        }
        return (string)$this->encoded['query'];
    }

    /**
     * Get fragment (get the anchor/hash/fragment (#anchor-12) link from URI "without" the hash)
     * @return string (ex: anchor-12)
     */
    public function getFragment(): string
    {
        if ($val = $this->getUniquePart("fragment")) {
            $this->encoded['fragment'] = Format\Str::value($val)->toggleUrlencode()->get();
        }
        return (string)$this->encoded['fragment'];
    }

    /**
     * Get formated URI
     * @return string
     */
    public function getUri(): string
    {
        if (is_null($this->build)) {
            $this->build = "";
            if ($scheme = $this->getScheme()) {
                $this->build .= "{$scheme}:";
            }
            if ($authority = $this->getAuthority()) {
                $this->build .= "//{$authority}";
            }
            if ($path = $this->getPath()) {
                $this->build .= "{$path}";
            }
            if ($query = $this->getQuery()) {
                $this->build .= "?{$query}";
            }
            if ($fragment = $this->getFragment()) {
                $this->build .= "#{$fragment}";
            }
        }
        return $this->build;
    }

    /**
     * Argv can be used with CLI command
     * E.g.: new Http\Uri($response->getUriEnv(["argv" => $argv]));
     * @return array
     */
    public function getArgv(): array
    {
        return $this->argv;
    }

    /**
     * Create new instance with same URI, BUT with a new scheme
     * @param  string $scheme
     * @return UriInterface
     */
    public function withScheme(string $scheme): UriInterface
    {
        $inst = clone $this;
        $inst->setPart("scheme", $scheme);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new userInfo
     * @param  string $user
     * @param  string $password
     * @return UriInterface
     */
    public function withUserInfo(string $user, ?string $password = null)
    {
        $inst = clone $this;
        $inst->setPart("user", $user)->setPart("pass", $password);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new host
     * @param  string $host
     * @return UriInterface
     */
    public function withHost(string $host): UriInterface
    {
        $inst = clone $this;
        $inst->setPart("host", $host);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new port
     * @param  int $post
     * @return UriInterface
     */
    public function withPort(?int $port): UriInterface
    {
        $inst = clone $this;

        $inst->setPart("port", $port);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new path
     * @param  string $path
     * @return UriInterface
     */
    public function withPath(string $path): UriInterface
    {
        $inst = clone $this;
        $inst->setPart("path", $path);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new query
     * @param  string $query
     * @return UriInterface
     */
    public function withQuery(string $query): UriInterface
    {
        $inst = clone $this;
        $inst->setPart("query", $query);
        return $inst;
    }

    /**
     * Create new instance with same URI, BUT with a new fragment
     * @param  string $fragment
     * @return UriInterface
     */
    public function withFragment(string $fragment): UriInterface
    {
        $inst = clone $this;
        $inst->setPart("fragment", $fragment);
        return $inst;
    }

    /**
     * With new parts
     * @param  array  $parts E.g. (parse_url or ["scheme" => "https", ...])
     * @return self
     */
    public static function withUriParts(array $parts): self
    {
        $inst = new self();
        $inst->parts = $parts;
        $inst->fillParts();
        return $inst;
    }

    /**
     * Return part if object found and has not yet been encoded
     * @param  string  $key
     * @return string|boolean
     */
    private function getUniquePart(string $key)
    {
        return (!is_null($this->{$key}) && is_null($this->encoded[$key])) ? $this->{$key} : null;
    }

    /**
     * Return part if object found and has not yet been encoded
     * @param  string  $key
     * @return string|null
     */
    public function getPart(string $key): ?string
    {
        return ($this->encoded[$key] ?? ($this->{$key} ?? null));
    }

    /**
     * Fill and encode all parts
     * @return void
     */
    private function fillParts(): void
    {
        $vars = get_object_vars($this);
        foreach ($vars as $k => $v) {
            $this->encoded[$k] = null;
            if (isset($this->parts[$k]) && ($p = $this->parts[$k])) {
                $this->{$k} = $p;
            }
        }
    }

    /**
     * Set/reset part (will tell the script to re-encode the specified part)
     * @param string $key   Part key (e.g. scheme, path, port...)
     * @param string|NULL $value New part value
     */
    protected function setPart(string $key, mixed $value)
    {
        $this->{$key} = $value;
        if (isset($this->encoded[$key])) {
            $this->encoded[$key] = null;
        }
        return $this;
    }
}
