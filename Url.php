<?php 

declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\UrlInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Method;

class Url implements UrlInterface
{
    
    private $request;
    private $uri;
    private $url;
    private $parts;
    private $vars;
    private $path;
    private $dirPath;
    private $fullPath;
    private $realPath;

    function __construct(RequestInterface $request, $path, string $dir = "")
    {
        $this->request = $request;
        $this->uri = $this->request->getUri();
        $this->parts = $path;

        //print_r($this->parts);
        //die();
        $this->fullPath = $this->uri->getPath();
        $this->dirPath = $this->getDirPath();
        $this->realPath = $this->getRealPath();
    }

    /**
     * Access http PSR URI message 
     */
    function __call($a, $b) {
        if(method_exists($this->uri, $a)) {
            return call_user_func_array([$this->uri, $a], $b);
        } else {
            throw new \BadMethodCallException("The method ({$a}) does not exist in UrlInterface or UriInterface.", 1);
        }
    }

    /**
     * With URI path type key
     * @param  string|array  $type
     * @return static
     */
    public function withType(null|string|array $type): self 
    {
        if(is_string($type)) $type = [$type];
        if(is_null($type)) $type = [];

        $inst = clone $this;
        $parts = $vars = array();
        foreach($inst->parts as $sel => $row) {
            if(in_array($sel, $type)) {
                if(is_array($row)) {
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

    public function reset(): self 
    {
        return $this->withType(NULL);
    }

    public function select(string|array $type): self 
    {
        return $this->withType($type);
    }

    /**
     * Add to URI path
     * @param array|string $arr
     * @return static
     */
    public function add(array|string $arr): self 
    {
        $inst = clone $this;
        if(is_string($arr)) $arr = [$arr];
        if(is_null($inst->vars)) $inst->vars = $inst->getVars();
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
        if(is_null($this->vars)) $this->vars = explode("/", $this->realPath);
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
        if(is_null($this->realPath)){
            $this->realPath = str_replace($this->getDirPath(), "", $this->uri->getPath());
        }

        return $this->realPath;
    }

    /**
     * Extract and get directories from the simulated htaccess path
     * @return string
     */
    public function getDirPath(): string 
    {
        if(is_null($this->dirPath)) {
            $this->dirPath = str_replace($_SERVER['DOCUMENT_ROOT'], "", $this->request->getUri()->getDir());
        }
        return $this->dirPath;
    }


    /**
     * Get last path item
     * @return string
     */
    public function get(): string|bool 
    {
        return $this->last();
    }

    /**
     * Get last path item
     * @return string
     */
    public function current(): string|bool 
    {
        return $this->last();
    }

    /**
     * Get last path item
     * @return string
     */
    public function last(): string|bool 
    {
        if(is_null($this->vars)) $this->vars = $this->getVars();
        return end($this->vars);
    }

    /**
     * Get first path item
     * @return string
     */
    public function first(): string 
    {
        if(is_null($this->vars)) $this->vars = $this->getVars();
        return reset($this->vars);
    }

    /**
     * Get travers to prev path item
     * @return string
     */
    public function prev(): string 
    {
        if(is_null($this->vars)) $this->end();
        return prev($this->vars);
    }

    /**
     * Get travers to next path item
     * @return string
     */
    public function next(): string 
    {
        if(is_null($this->vars)) $this->reset();
        return next($this->vars);
    }

    /**
     * Get root URL
     * @param  string $path add to URI
     * @return string
     */
    public function getRoot(string $path = ""): string
    {
        $url = "";
        if($scheme = $this->getScheme()) $url .= "{$scheme}:";
        if($authority = $this->getHost()) $url .= "//{$authority}";
        if($dir = $this->getDirPath()) $url .= rtrim($dir, "/");

        return $url.$path;
    }

    /**
     * Get full URL (path is changeable with @add and @withType method)
     * @param  string $setPath add to URI
     * @return string
     */
    public function getUrl(string $setPath = ""): string 
    {
        $this->url = "";
        if($scheme = $this->getScheme()) $this->url .= "{$scheme}:";
        if($authority = $this->getHost()) $this->url .= "//{$authority}";
        if($dir = $this->getDirPath()) $this->url .= "{$dir}";
        if($path = $this->getRealPath()) $this->url .= "{$path}";  
        return $this->url.$setPath;
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
        if($isProd) {
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

    final public function filterParts($vars): array 
    {
        if((is_array($vars) && count($vars) === 0 && ($path = $this->uri->getRealPath())) || (is_string($vars) && $vars)) {
            $vars = explode("/", $path);
        }
        // True: rawurlencode
        return Method::_value($vars)->get(true);
    }

}
