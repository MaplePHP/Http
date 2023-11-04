<?php

namespace Http\tests\Rep;

class PagesRep
{
    //\Http\tests\Rep\PageService $ser, \Http\tests\Rep\PageServiceB $ser2
    public function __construct(\Http\tests\Rep\PagesLast $last)
    {
    }

    public function about()
    {
        // Change meta titles
        return "ABOUT";
    }
}
