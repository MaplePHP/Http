<?php

namespace PHPFuse\Http;

use PHPFuse\Output\Format;

class Method
{

    private $request;

    /**
     * Start instance 
     * get(slug|key): Will catch GET result
     * post(slug|key): Will catch POST result
     * value([test => lorem ispsum]): Custom string or array value
     * @param  string|array $a
     * @return self
     */
    static function __callStatic($k, $a): self
    {
        $inst = new static();
        switch($k) {
            case '_get':
                $inst->request = $inst->getRawGet($a[0]);
            break;
            case '_post':
                $inst->request = $inst->getRawPost($a[0]);
            break;
            case '_value': 
                $inst->request = $a[0];
            break;
            default:
                throw new \Exception("The method \"{$k}\" does not exist in ".__CLASS__."", 1);
            break;
        }

        return $inst;
    }

    /**
     * Get XXS "Protected" result
     * @return string|array|null
     */
    function get(bool $encode = false) 
    {
        if(is_array($this->request)) {
            $this->request = Format\Arr::value($this->request)->walk(function($value) use($encode) {
                $uri = Format\Uri::value((string)$value)->xxs();
                if($encode) $uri->rawurlencode();
                return $uri->get();
            })->get();

        } else {
            $this->request = Format\Uri::value($this->request)->xxs()->get();
        }

        return $this->request;
    }

    /**
     * Get "UNPROTECTED" result
     * BE CAREFUL: You need to manualy escape result if presented in body
     * @return string|array|null
     */
    function raw() 
    {
        return $this->request;
    }

    /**
     * Protected: HTTP GET val. USE HTTP class instead!
     * @param  string $key
     * @return value
     */
    private function getRawGet($key) 
    {
        return ($_GET[$key] ?? NULL);
    }

    /**
     * UPROTECTED: HTTP POST val. USE HTTP class instead!
     * @param  string $key
     * @return value
     */
    private function getRawPost($key) 
    {
        return ($_POST[$key] ?? NULL);
    }
}
