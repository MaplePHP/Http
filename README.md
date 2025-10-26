

# MaplePHP - A Full-Featured PSR-7 Compliant HTTP Library

**MaplePHP/Http** is a powerful and easy-to-use PHP library that fully supports the PSR-7 HTTP message interfaces. It simplifies handling HTTP requests, responses, streams, URIs, and uploaded files, making it an excellent choice for developers who want to build robust and interoperable web applications.

With MaplePHP, you can effortlessly work with HTTP messages while adhering to modern PHP standards, ensuring compatibility with other PSR-7 compliant libraries.

## Why Choose MaplePHP?

- **Full PSR-7 Compliance**: Seamlessly integrates with other PSR-7 compatible libraries and frameworks.
- **User-Friendly API**: Designed with developers in mind for an intuitive and straightforward experience.
- **Comprehensive Functionality**: Handles all aspects of HTTP messaging, including requests, responses, streams, URIs, and file uploads.
- **Flexible and Extensible**: Easily adapts to projects of any size and complexity.

## Installation

Install MaplePHP via Composer:

```bash
composer require maplephp/http
```



### Handling HTTP Requests

#### Creating a Server Request

To create a server request, use the `ServerRequest` class:

```php
use MaplePHP\Http\Environment;use MaplePHP\Http\ServerRequest;use MaplePHP\Http\Uri;

// Create an environment instance (wraps $_SERVER)
$env = new Environment();

// Create a URI instance from the environment
$uri = new Uri($env->getUriParts());

// Create the server request
$request = new ServerRequest($uri, $env);
```

#### Accessing Request Data

You can easily access various parts of the request:

```php
// Get the HTTP method
$method = $request->getMethod(); // e.g., GET, POST

// Get request headers
$headers = $request->getHeaders();

// Get a specific header
$userAgent = $request->getHeaderLine('User-Agent');

// Get query parameters
$queryParams = $request->getQueryParams();

// Get parsed body (for POST requests)
$parsedBody = $request->getParsedBody();

// Get uploaded files
$uploadedFiles = $request->getUploadedFiles();

// Get server attributes
$attributes = $request->getAttributes();
```

#### Modifying the Request

Requests are immutable; methods that modify the request return a new instance:

```php
// Add a new header
$newRequest = $request->withHeader('X-Custom-Header', 'MyValue');

// Change the request method
$newRequest = $request->withMethod('POST');

// Add an attribute
$newRequest = $request->withAttribute('user_id', 123);
```

### Managing HTTP Responses

#### Creating a Response

Create a response using the `Response` class:

```php
use MaplePHP\Http\Response;use MaplePHP\Http\Stream;

// Create a stream for the response body
$body = new Stream('php://temp', 'rw');

// Write content to the body
$body->write('Hello, world!');
$body->rewind();

// Create the response with the body
$response = new Response($body);
```

#### Setting Status Codes and Headers

You can set the HTTP status code and headers:

```php
// Set the status code to 200 OK
$response = $response->withStatus(200);

// Add headers
$response = $response->withHeader('Content-Type', 'text/plain');

// Add multiple headers
$response = $response->withAddedHeader('X-Powered-By', 'MaplePHP');
```

#### Sending the Response

To send the response to the client:

```php
// Output headers
foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

// Output status line
header(sprintf(
    'HTTP/%s %s %s',
    $response->getProtocolVersion(),
    $response->getStatusCode(),
    $response->getReasonPhrase()
));

// Output body
echo $response->getBody();
```

### Working with Streams

Streams are used for the message body in requests and responses.

#### Creating a Stream
Reading and Writing with stream

```php
use MaplePHP\Http\Stream;

// Create a stream from a file
//$fileStream = new Stream('/path/to/file.txt', 'r');

// Create a stream from a string
$memoryStream = new Stream(Stream::MEMORY);
//$memoryStream = new Stream('php://memory', 'r+'); // Same as above
$memoryStream->write('Stream content');

// Write to the stream
$memoryStream->write(' More content');

// Read from the stream
$memoryStream->rewind();
echo $memoryStream->getContents();
// Result: 'Stream content More content'
```


#### Using Streams in Requests and Responses

```php
// Set stream as the body of a response
$response = $response->withBody($memoryStream);
```

### Manipulating URIs

URIs are used to represent resource identifiers.

#### Creating and Modifying URIs

```php
// Create a URI instance
$uri = new Uri('http://example.com:8000/path?query=value#fragment');

// Modify the URI
$uri = $uri->withScheme('https')
            ->withUserInfo('guest', 'password123')
            ->withHost('example.org')
            ->withPort(8080)
            ->withPath('/new-path')
            ->withQuery('query=newvalue')
            ->withFragment('section1');

// Convert URI to string
echo $uri; // Outputs the full URI
//Result: https://guest:password123@example.org:8080/new-path?query=newvalue#section1
```

#### Accessing URI Components

```php
echo $uri->getScheme();     // 'http'
echo $uri->getUserInfo();   // 'guest:password123'
echo $uri->getHost();       // 'example.org'
echo $uri->getPath();       // '/new-path'
echo $uri->getQuery();      // 'key=newvalue'
echo $uri->getFragment();   // 'section1'
echo $uri->getAuthority();  // 'guest:password123@example.org:8080'
```

### Handling Uploaded Files

Manage file uploads with ease using the `UploadedFile` class.

#### Accessing Uploaded Files

```php
// Get uploaded files from the request
$uploadedFiles = $request->getUploadedFiles();

// Access a specific uploaded file
$uploadedFile = $uploadedFiles['file_upload'];

// Get file details
$clientFilename = $uploadedFile->getClientFilename();
$clientMediaType = $uploadedFile->getClientMediaType();

// Move the uploaded file to a new location
$uploadedFile->moveTo('/path/to/uploads/' . $clientFilename);
```

### Using the HTTP Client

Send HTTP requests using the built-in HTTP client.

#### Sending a Request

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
if ($response->getStatusCode() === 200) {
    // Parse the response body
    $data = json_decode($response->getBody()->getContents(), true);
    // Use the data
    echo 'User Name: ' . $data['name'];
} else {
    echo 'Error: ' . $response->getReasonPhrase();
}
```

## Conclusion

**MaplePHP/Http** is a comprehensive library that makes working with HTTP in PHP a breeze. Its full PSR-7 compliance ensures that your applications are built on solid, modern standards, promoting interoperability and maintainability.

Whether you're handling incoming requests, crafting responses, manipulating URIs, working with streams, or managing file uploads, MaplePHP provides a clean and intuitive API that simplifies your development process.

Get started today and enhance your PHP applications with MaplePHP!
