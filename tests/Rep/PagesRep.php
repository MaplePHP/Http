<?php

namespace Http\tests\Rep;

class PagesRep {


    //\Http\tests\Rep\PageService $ser, \Http\tests\Rep\PageServiceB $ser2
    function __construct(\Http\tests\Rep\PagesLast $last) {
    }

    function about() {
        // Change meta titles
        return "ABOUT";
      
    }

}