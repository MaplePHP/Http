<?php

declare(strict_types=1);

namespace MaplePHP\Http;

use MaplePHP\Http\Interfaces\UrlInterface;
use MaplePHP\Http\Interfaces\UrlHandlerInterface;
use MaplePHP\Http\Interfaces\UriInterface;
use MaplePHP\Http\Interfaces\RequestInterface;

class Url implements UrlInterface
{
    private $request;
    private $uri;
    private $parts;
    private $vars;
    private $fullPath;
    private $dirPath;
    private $realPath;
    private $publicDirPath;
    private $handler;

    public function __construct(RequestInterface $request, array $parts)
    {
        $this->request = $request;
        $this->uri = $this->request->getUri();
        $this->parts = $parts;
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

            if(is_string($this->dirPath) && $root.$this->dirPath !== $_ENV['APP_DIR']) {
                throw new \Exception("Could not validate the dirPath", 1);
            }
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
     * Get root URL DIR
     * @param  string   $path       add to URI
     * @param  bool     $endSlash   add slash to the end of root URL (default false)
     * @return string
     */
    public function getRootDir(string $path = "", bool $endSlash = false): string
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
        return $url. (($endSlash) ? "/" : "") . $path;
    }

    /**
     * Get root URL
     * @param  string   $path       add to URI
     * @param  bool     $endSlash   add slash to the end of root URL (default false)
     * @return string
     */
    public function getRoot(string $path = "", bool $endSlash = false): string
    {
        $url = $this->getRootDir("/");
        if (!is_null($this->handler)) {
            $url .= $this->handler->getPublicDirPath();
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
     * Not required but recommended. You can pass on URL shortcuts to the class
     * E.g. getPublic, getCss
     * @param UrlHandlerInterface $handler
     */
    public function setHandler(UrlHandlerInterface $handler): void
    {
        $this->handler = $handler;
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
        if (!is_null($this->handler) && method_exists($this->handler, $method)) {
            return call_user_func_array([$this->handler, $method], $args);
        } else if (method_exists($this->uri, $method)) {
            return call_user_func_array([$this->uri, $method], $args);
        } else {
            throw new \BadMethodCallException("The method ({$method}) does not exist in \"".__CLASS__."\" (UrlInterface or UriInterface).", 1);
        }
    }
}
