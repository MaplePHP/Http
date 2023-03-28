<?php

namespace Http\tests\Rep;

class PageServiceB {


    function __construct(\Http\tests\Rep\PagesRep $rep) {
    }

    function about() {
        // Change meta titles
        return "ABOUT";
      
    }

}