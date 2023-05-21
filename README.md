# PHP Fuse - PSR Http Message
The library is fully integrated with PSR Http Message and designed for use with PHP Fuse framework.

## Request
None of the construct attributes are required and will auto propagate if you leave them be.
```php
$request = new Http\ServerRequest(
	(array) Cookies, 
	(array) QueryParams, 
	(array) Files, 
	(array) ParsedBody, 
	(array) Attribute,
	(UriInterface) Uri
);
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
$request = new Http\Response(
	**(StreamInterface) Body,** 
	(array) Headers, 
	(int) status, 
	(?string) phrase, 
	(?string) version
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
## Stream
None of the construct attributes are required and will auto propagate if you leave them be.
```php
$stream = new Http\Stream(
	(mixed) Stream
	(string) permission
);
```
#### Basic stream example
```php
$stream = new Http\Stream(Http\Stream:TEMP);
$stream->write("Hello world");
$stream->seek(0);
echo $stream->read(); // Hello world

$upload = new Http\UploadedFile($stream);
$upload->moveTo("/var/www/html/upload/log.txt"); // Place Hello world in txt file
```
