<?php

namespace Http\tests\Rep;
use PHPFuse\Container\Interfaces\ContainerInterface;
class PagesLast {


    function __construct(ContainerInterface $con) {
        
    }

    function about() {
        // Change meta titles
        return "ABOUT";
      
    }

}