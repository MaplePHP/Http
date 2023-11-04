<?php

namespace Http\tests\Rep;

use PHPFuse\Container\Interfaces\ContainerInterface;

class PagesLast
{
    public function __construct(ContainerInterface $con)
    {
    }

    public function about()
    {
        // Change meta titles
        return "ABOUT";
    }
}
