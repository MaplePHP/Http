<?php

namespace Http\tests\Rep;

class PageServiceB
{
    public function __construct(\Http\tests\Rep\PagesRep $rep)
    {
    }

    public function about()
    {
        // Change meta titles
        return "ABOUT";
    }
}
