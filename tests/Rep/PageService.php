<?php

namespace Http\tests\Rep;

class PageService
{
    private $about;

    public function __construct(\Http\tests\Rep\PageServiceB $ser)
    {
        $this->about = "wdwqdwqdqwdqw";
    }

    public function about()
    {
        // Change meta titles
        return $this->about;
    }
}
