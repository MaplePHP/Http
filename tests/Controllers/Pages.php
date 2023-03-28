<?php

namespace Http\tests\Controllers;

use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Helpers\Interfaces\UrlInterface;

use PHPFuse\Container\Interfaces\ContainerInterface;

class Pages {

    private $container;

    
    function __construct(ContainerInterface $container, UrlInterface $url) {
        $this->container = $container;
    }

    function about(ResponseInterface $response, RequestInterface $request) {
        // Change meta titles

        
        $this->container->get("domHead")->getElement("title")->setValue("ABOUT");
        $this->container->get("domHead")->getElement("description")->attr("content", "Changed!");

        $this->container->get("template")->setPartial("breadcrumb", [
            "name" => "ABOUT US",
            "content" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit.",
            "date" => "2023-02-30 15:33:22",
            "feed" => [
                [
                    "headline" => "test 1", 
                    "description" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sunt, architecto."
                ],
                [
                    "headline" => "test 2", 
                    "description" => "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sunt, architecto."
                ]
            ]
        ]);

        //->withStatus(403)
        return $response;
    }

    
    function __invoke(ResponseInterface $response, RequestInterface $request) {
        $response = $response->withHeader("Content-type", "application/json; charset=UTF-8");
        $response->getBody()->write(json_encode(["status" => 2, "message" => "THIS HAS BEEN INVOKED"]));
        return $response;
    }

}