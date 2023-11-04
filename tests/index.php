<?php

ini_set('display_errors', "1");
ini_set('error_reporting', (string)E_ALL);
use PHPFuse\Http;
use PHPFuse\Container\Container;

$prefix = "PHPFuse";
$dir = dirname(__FILE__)."/../";

/**
 * Emitter dependancy:
 * nikic/fast-route
 * PHPFuse/Container
 */
require_once("{$dir}../_vendors/composer/vendor/autoload.php");

spl_autoload_register(function ($class) use ($dir, $prefix) {
    $classFilePath = null;
    $class = str_replace("\\", "/", $class);
    $exp = explode("/", $class);
    $sh1 = array_shift($exp);
    $path = implode("/", $exp).".php";
    if ($sh1 !== $prefix) {
        $path = "{$sh1}/{$path}";
    }

    $filePath = $dir."../".$path;


    if (!is_file($filePath)) {
        throw new \Exception("Could not require file: {$class}", 1);
    }

    require_once($filePath);
});


$stream = new Http\Stream(Http\Stream::TEMP);
$response = new Http\Response($stream, [
    "Content-type" => "text/html; charset=UTF-8",
    "X-Frame-Options" => "SAMEORIGIN",
    "X-XSS-Protection" => "1",
    "X-Content-Type-Options" => "nosniff"
]);

$request = new Http\ServerRequest();
$container = new Container();
$routes = new Http\RouterDispatcher($request);
$emitter = new Http\Emitter($container);


// bool $displayError, bool $niceError, bool $logError, string $logErrorFile
$emitter->errorHandler(true, true, true, "/var/www/html/systems/logger.txt");

//$www = new \Http\tests\Rep\PagesRep(new \Http\tests\Rep\PageService());
//$emitter->setRouterCacheFile("www/www");

$emitter->getTemplate()
->setIndexDir(dirname(__FILE__)."/resources/")
->setViewDir(dirname(__FILE__)."/resources/views/")
->setPartialDir(dirname(__FILE__)."/resources/partials/")
->bindToBody(
    "httpStatus",
    PHPFuse\DTO\Format\Arr::value(PHPFuse\Http\Response::PHRASE)->unset(200, 201, 202)->arrayKeys()->get()
)
->setIndex("index")
->setView("main");

// DOM Templating
$container->set("domHead", 'PHPFuse\Output\Dom\Document::dom', ["head"]);

$dom = $container->get("domHead");
$dom->bindTag("title", "title")->setValue("Meta title");
$dom->bindTag("meta", "description")->attr("name", "Description")->attr("content", "Lorem ipsum dolor sit amet.");
$dom->bindTag("meta", "viewport")->attr("name", "Viewport")->attr("content", "width=device-width, initial-scale=1");

// Router Path
$routes->setDispatchPath("/".Http\Method::_get("page")->get());
$routes->map(["GET", "HEAD"], "/", function ($response, $request) use ($container) {

    // Change meta titles
    $container->get("domHead")->getElement("title")->setValue("HOME");
    $container->get("domHead")->getElement("description")->attr("content", "Changed!");

    $container->get("template")->setPartial("breadcrumb", [
        "name" => "HOME",
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

    return $response;
});

$routes->get("/{page:test}[/{cat:soffa}]", ['Http\tests\Controllers\Pages', "about"]);
$routes->get("/test2", \Http\tests\Controllers\Pages::class);

$routes->dispatch($response, function ($dispatchStatus, $response, $url) use ($container, $request) {

    switch ($dispatchStatus) {
        case Http\RouterDispatcher::NOT_FOUND:
            return $response->withStatus(404);
            break;
        case Http\RouterDispatcher::METHOD_NOT_ALLOWED:
            return $response->withStatus(403);
            break;
        case Http\RouterDispatcher::FOUND:
            // Add a class that will where it's instance will be remembered through the app and its controllers
            // To do this, you must first create an interface of the class, which will become its uniqe identifier.
            PHPFuse\Container\Reflection::interfaceFactory(function ($c, $s, $i)
 use ($container, $request, $response, $url) {
                switch ($s) {
                    case "UrlInterface":
                        return $url;
                        break;
                    case "ContainerInterface":
                        return $container;
                        break;
                    case "RequestInterface":
                        return $request;
                        break;
                    case "ResponseInterface":
                        return $response;
                        break;
                }
            });

            break;
    }
});


// If you set a buffered response string it will get priorities agains all outher response
$emitter->outputBuffer($routes->getBufferedResponse());

$emitter->run($routes->response(), $routes->request());
