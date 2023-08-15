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

spl_autoload_register(function($class) use($dir, $prefix) {
    $classFilePath = NULL;
    $class = str_replace("\\", "/", $class);
    $exp = explode("/", $class);
    $sh1 = array_shift($exp);
    $path = implode("/", $exp).".php";
    if($sh1 !== $prefix) $path = "{$sh1}/{$path}";
    $filePath = $dir."../".$path;
    if(!is_file($filePath)) throw new \Exception("Could not require file: {$class}", 1);
    require_once($filePath);    
});

/*
// STREAM MOVE
$upload = new Http\UploadedFile('/var/www/html/systems/logger.txt');
$upload->moveTo("/var/www/html/systems/copyto/logger2.txt");

// This will do the same as above, but showing that you can easly generate a file from a existing stream
$stream = new Http\Stream('/var/www/html/systems/logger.txt');
$upload = new Http\UploadedFile($stream);
$upload->moveTo("/var/www/html/systems/copyto/logger.txt");

// Create a new file
$upload = new Http\UploadedFile(Http\Stream::TEMP);
$upload->getStream()->write("Lorem ipsum dolor sit amet");
$upload->moveTo("/var/www/html/systems/copyto/logger4.txt");
*/

if(isset($_POST['submit'])) {

    // UPLOAD FILE
    $upload = new Http\UploadedFile($_FILES['fileToUpload']);
    $upload->moveTo("/var/www/html/systems/copyto/test.jpg");

    // DO SOMETHING TO FILE (This will stream copy)
    $stream = new Http\UploadedFile($upload->getStream());
    $stream->moveTo("/var/www/html/systems/copyto/test22.jpg");

    die("DONE");
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="form.php" enctype="multipart/form-data" method="post">
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Upload Image" name="submit">
    </form>
</body>
</html>