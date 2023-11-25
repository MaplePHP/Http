<?php

declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\UrlInterface;
use PHPFuse\Http\Interfaces\UriInterface;
use PHPFuse\Http\Interfaces\RequestInterface;

class Url implements UrlInterface
{
    private $request;
    private $uri;
    private $parts;
    private $vars;
    //private $path;
    private $fullPath;
    private $dirPath;
    private $realPath;

    public function __construct(RequestInterface $request, $path)
    {
        $this->request = $request;
        $this->uri = $this->request->getUri();
        $this->parts = $path;
        $this->fullPath = $this->uri->getPath();
        $this->dirPath = $this->getDirPath();
        $this->realPath = $this->getRealPath();
    }

    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * Access http PSR URI message
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Access http PSR URI message
     * @param  string $method
     * @param  array $args
     * @return mixed
     * @psalm-taint-sink
     */
    public function __call($method, $args): mixed
    {
        if (method_exists($this->uri, $method)) {
            return call_user_func_array([$this->uri, $method], $args);
        } else {
            throw new \BadMethodCallException("The method ({$method}) does not exist in UrlInterface or UriInterface.", 1);
        }
    }

    /**
     * With URI path type key
     * @param  null|string|array  $type (Default null: reset)
     * @return static
     */
    public function withType(null|string|array $type = null): self
    {
        if (is_string($type)) {
            $type = [$type];
        }
        if (is_null($type)) {
            $type = [];
        }

        $inst = clone $this;
        $parts = $vars = array();
        foreach ($inst->parts as $sel => $row) {
            if (in_array($sel, $type)) {
                if (is_array($row)) {
                    $vars = array_merge($vars, $row);
                } else {
                    $vars[] = $row;
                }

                $parts[$sel] = $row;
            }
        }

        //$inst->vars = $vars;
        $inst->parts = $parts;
        $inst->realPath = implode("/", $vars);
        $inst->vars = explode("/", $inst->realPath);
        return $inst;
    }

    /**
     * Same as withType except that you Need to select a part
     * @param  string|array  $type
     * @return static
     */
    public function select(string|array $type): self
    {
        return $this->withType($type);
    }

    /**
     * Same as withType except it will only reset
     * @return static
     */
    public function reset(): self
    {
        return $this->withType(null);
    }

    /**
     * Add to URI path
     * @param array|string $arr
     * @return static
     */
    public function add(array|string $arr): self
    {
        $inst = clone $this;
        if (is_string($arr)) {
            $arr = [$arr];
        }
        if (is_null($inst->vars)) {
            $inst->vars = $inst->getVars();
        }
        $inst->vars = array_merge($inst->vars, $arr);
        $inst->realPath = implode("/", $inst->vars);
        return $inst;
    }

    /**
     * Get vars/path as array
     * @return array
     */
    public function getVars(): array
    {
        if (is_null($this->vars)) {
            $this->vars = explode("/", $this->realPath);
        }
        return $this->vars;
    }

    /**
     * Get vars/path as array
     * @return array
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Get real htaccess path (possible directories filtered out)
     * @return string
     */
    public function getRealPath(): string
    {
        if (is_null($this->realPath)) {
            $this->realPath = str_replace($this->getDirPath(), "", $this->uri->getPath());
        }
        if (!is_string($this->realPath)) {
            throw new \Exception("Could not create realPath", 1);
        }
        return $this->realPath;
    }

    /**
     * Extract and get directories from the simulated htaccess path
     * @return string
     */
    public function getDirPath(): string
    {
        if (is_null($this->dirPath)) {
            $root = (isset($_SERVER['DOCUMENT_ROOT'])) ? $_SERVER['DOCUMENT_ROOT'] : "";
            $root = htmlspecialchars($root, ENT_QUOTES, 'UTF-8');
            $this->dirPath = str_replace($root, "", $this->request->getUri()->getDir());
        }
        if (!is_string($this->dirPath)) {
            throw new \Exception("Could not create dirPath", 1);
        }
        return $this->dirPath;
    }
    
    /**
     * Get expected slug from path
     * @return string
     */
    public function get(): string
    {
        return $this->last();
    }

    /**
     * Get expected slug from path
     * @return string
     */
    public function current(): string
    {
        return $this->last();
    }

    /**
     * Get last path item
     * @return string
     */
    public function last(): string
    {
        if (is_null($this->vars)) {
            $this->vars = $this->getVars();
        }
        return end($this->vars);
    }

    /**
     * Get first path item
     * @return string
     */
    public function first(): string
    {
        if (is_null($this->vars)) {
            $this->vars = $this->getVars();
        }
        return reset($this->vars);
    }

    /**
     * Get travers to prev path item
     * @return string
     */
    public function prev(): string
    {
        if (is_null($this->vars)) {
            $this->end();
        }
        return prev($this->vars);
    }

    /**
     * Get travers to next path item
     * @return string
     */
    public function next(): string
    {
        if (is_null($this->vars)) {
            $this->reset();
        }
        return next($this->vars);
    }

    /**
     * Get root URL
     * @param  string   $path       add to URI
     * @param  bool     $endSlash   add slash to the end of root URL (default false)
     * @return string
     */
    public function getRoot(string $path = "", bool $endSlash = false): string
    {
        $url = "";
        if ($scheme = $this->getScheme()) {
            $url .= "{$scheme}:";
        }
        if ($authority = $this->getHost()) {
            $url .= "//{$authority}";
        }
        if ($dir = $this->getDirPath()) {
            $url .= rtrim($dir, "/");
        }
        return $url . (($endSlash) ? "/" : "") . $path;
    }

    /**
     * Get full URL (path is changeable with @add and @withType method)
     * @param  string $addToPath add to URI
     * @return string
     */
    public function getUrl(string $addToPath = ""): string
    {
        $url = $this->getRoot("", true);
        if ($path = $this->getRealPath()) {
            $path = ltrim($path, "/");
            $url .= "{$path}";
        } else {
            $addToPath = ltrim($addToPath, "/");
        }
        return $url . $addToPath;
    }

    /**
     * Get URL to public directory
     * @param  string $path  add to URI
     * @return string
     */
    public function getPublic(string $path = ""): string
    {
        return $this->getRoot("/public/{$path}");
    }

    /**
     * Get URL to resources directory
     * @param  string $path  add to URI
     * @return string
     */
    public function getResource(string $path = ""): string
    {
        return $this->getRoot("/resources/{$path}");
    }

    /**
     * Get URL to js directory
     * @param  string $path  add to URI
     * @return string
     */
    public function getJs(string $path, bool $isProd = false): string
    {
        if ($isProd) {
            return $this->getPublic("js/{$path}");
        }
        return $this->getResource("js/{$path}");
        //$dir = ($distDir && getenv("APP_ENV") === "production") ? "dist/" : "";
        //return $this->getPublic("js/{$dir}{$path}");
    }

    /**
     * Get URL to css directory
     * @param  string $path  add to URI
     * @return string
     */
    public function getCss(string $path): string
    {
        return $this->getPublic("css/{$path}");
    }
}
