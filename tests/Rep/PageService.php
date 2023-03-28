<?php

namespace Http\tests\Rep;

class PageService {

    private $about;

    function __construct(\Http\tests\Rep\PageServiceB $ser) {
        $this->about = "wdwqdwqdqwdqw";
    }

    function about() {
        // Change meta titles
        return $this->about;
      
    }

}