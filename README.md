# MaplePHP - A Full-Featured PSR-7 Compliant HTTP Library
MaplePHP/Http is a PHP library that brings simplicity and adherence to the PSR-7 standard into handling HTTP messages, requests, and responses within your web projects. It's thoughtfully designed to make the integration of crucial elements like Stream, Client, Cookies, UploadedFile, and Headers straightforward and efficient.

By aligning closely with PSR-7, MaplePHP/Http facilitates better interoperability between web components, allowing for more effective communication within applications. Whether you're working with client-side cookies, managing headers in a request, or handling file uploads through UploadedFile, this library has got you covered, making these tasks more manageable and less time-consuming.

MaplePHP/Http aims to support your web development by offering a reliable foundation for working with HTTP messaging, streamlining the process of dealing with requests and responses. It's a practical choice for developers looking to enhance their applications with PSR-7 compliant HTTP handling in a user-friendly way.


## Installation

```
composer require maplephp/http
```

## Initialize
The **examples** below is utilizing the "namespace" below just to more easily demonstrate the guide.

```php
use MaplePHP\Http;
```


## Request

```php
$request = new Http\ServerRequest(UriInterface $uri, EnvironmentInterface $env);
```
####  Get request method
```php
echo $request->getMethod(); // GET, POST, PUT, DELETE
```
####  Get Uri instance
```php
$uri = $request->getUri(); // UriInterface
echo $uri->getScheme(); // https
echo $uri->getAuthority(); // [userInfo@]host[:port]
echo $uri->getUserInfo(); // username:password
echo $uri->getHost(); // example.com, staging.example.com, 127.0.0.1, localhost
echo $uri->getPort(); // 443
echo $uri->getPath(); // /about-us/workers
echo $uri->getQuery(); // page-id=12&filter=2
echo $uri->getFragment(); // anchor-12 (The anchor hash without "#")
echo $uri->getUri(); // Get the full URI
```
## Response
Only the **(StreamInterface) Body** attribute is required and the rest will auto propagate if you leave them be.
```php
$response = new Http\Response(
	StreamInterface $body,
    ?HeadersInterface $headers = null,
    int $status = 200,
    ?string $phrase = null,
    ?string $version = null
);
```
####  Get Status code
```php
echo $response->getStatusCode(); // 200
```
####  Get Status code
```php
$newInst = $response->withStatus(404);
echo $newInst->getStatusCode(); // 404
echo $newInst->getReasonPhrase(); // Not Found
```
## Message
Both Request and Response library will inherit methods under Message but with different information.
```php
echo $response->getProtocolVersion(); // 1.1
echo $response->getHeaders(); // Array with all headers
echo $response->hasHeader("Content-Length"); // True
echo $response->getHeader("Content-Length"); // 1299
echo $response->getBody(); // StreamInterface
```

## A standard example usage
```php
$stream = new Http\Stream(Http\Stream::TEMP);
$response = new Http\Response($stream);
$env = new Http\Environment();
$request = new Http\ServerRequest(new Http\Uri($env->getUriParts()), $env);
```

## Stream
None of the construct attributes are required and will auto propagate if you leave them be.
```php
$stream = new Http\Stream(
	(mixed) Stream
	(string) permission
);
```
### Basic stream examples

#### Write to stream
```php
$stream = new Http\Stream(Http\Stream::TEMP);
if ($stream->isSeekable()) {
    $stream->write("Hello world");
    //echo $stream; // will print Hello world
    // Or
    $stream->rewind();
    echo $stream->getContents(); // Hello world
    // Or Same as above
    //echo $stream->read($stream->getSize());
}
```

#### Get file content with stream
```php
$stream = new Http\Stream("/var/www/html/YourApp/dir/dir/data.json");
echo $stream->getContents();
```

#### Upload a stream to the server
```php
$upload = new Http\UploadedFile($stream);
$upload->moveTo("/var/www/html/upload/log.txt"); // Place Hello world in txt file
```

### Create a request
The client will be using curl, so it's essential to ensure that it is enabled in case it has been disabled for any reason.
```php
// Init request client
$client = new Http\Client([CURLOPT_HTTPAUTH => CURLAUTH_DIGEST]); // Pass on Curl options

// Create request data
$request = new Http\Request(
    "POST", // The HTTP Method (GET, POST, PUT, DELETE, PATCH)
    "https://admin:mypass@example.com:443/test.php", // The Request URI
    ["customHeader" => "lorem"], // Add Headers, empty array is allowed
    ["email" => "john.doe@example.com"] // Post data
);

// Pass request data to client and POST
$response = $client->sendRequest($request);

// Get Stream data
var_dump($response->getBody()->getContents());
```
