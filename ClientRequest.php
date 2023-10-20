<?php
/**
 * @Package: 	Fuse Curl request
 * @Author: 	Daniel Ronkainen
 * @Licence: 	The MIT License (MIT), Copyright © Daniel Ronkainen
 				Don't delete this comment, its part of the license.
 * @Version: 	1.0.0
 */


/*


$client = new Client();

// Define the request
$request = new Request('GET', 'https://example.com');

// Send the request
$response = $client->send($request, ['http_errors' => false]);

// Check the response
if ($response->getStatusCode() === 200) {
    // Request was successful
    echo $response->getBody();
} else {
    // Handle errors
    echo 'Request failed with status code: ' . $response->getStatusCode();
}

 */
// ClientExceptionInterface 
// 
// RequestExceptionInterface, Request is invalid (e.g. method is missing), Runtime request errors (e.g. the body stream is not seekable)
// 
// NetworkExceptionInterface, network issues, no response (empty)

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\ClientInterface;

use PHPFuse\Http\Exceptions\ClientException;
use PHPFuse\Http\Exceptions\RequestException;
use PHPFuse\Http\Exceptions\NetworkException;

class Client implements ClientInterface {

	
	private $request;
	private $options;
	private $ch;
	private $url;
	private $timeout = 30;

	private $requestResponse;
	private $requestHeaders;

	function __construct(RequestInterface $request, array $options = []) {
		
		$this->request = $request;
		$this->options = $options;
	}



	function send(): ResponseInterface
	{


		$this->sendRequest($this->request);

		return $this->createResponse();
	}


	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 * @param  RequestInterface $request
	 * @return ResponseInterface
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface 
	{

		$url = $request->getUri()->getScheme()."://".$request->getUri()->getHost().$request->getUri()->getPath();

		
		$this->setOption(CURLOPT_URL, $url);
		$this->setOption(CURLOPT_RETURNTRANSFER, true);
		if(!is_null($request->getUri()->getPort())) {
			$this->setOption(CURLOPT_PORT, (int)$request->getUri()->getPort());
		}
		$this->ch = curl_init();
		$this->buildOptions();

		try {
			switch($request->getMethod()) {
				case 'GET':
					$this->get();
				break;
				case 'POST':
					$this->post();
				break;
				case 'PUT':
					$this->put();
				break;
				case 'PATCH':
					$this->patch();
				break;
				case 'DELETE':
					$this->delete();
				break;
				default:
					throw new \InvalidArgumentException('The current verb/method ('.$request->getMethod().') is an invalid request method.');
			}			

		} catch (RequestException $e) {
			curl_close($this->ch);
			throw $e;
		}


		$status = (int)($this->requestHeaders['http_code'] ?? 200);


		if($status === 0) {
			throw new RequestException("Error Processing Request", 1);
		}


		return $this->createResponse()->withStatus($status);
	}

	final protected function createResponse(): ResponseInterface
	{
		$stream = new Stream(Stream::TEMP);
		$stream->write($this->requestResponse);
		if(!$stream->isSeekable()) {
			throw new RequestExceptionInterface("Request body is not seekable", 1);
		}
		$stream->seek(0);

		return new Response($stream);
	}


	function auth() {
		if(!is_null($this->basicAuth)) {
			$this->setHeader("Authorization", "Basic {$this->basicAuth}");
		} else {
			curl_setopt($this->ch, CURLOPT_HTTPAUTH, $this->httpAuth);
			curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
		}
	}


	private function buildRequest(): void 
	{
		//$this->ch = curl_init();
		//$this->buildOptions();
	}


	final protected function createRequest(): void 
	{
		$this->requestResponse = curl_exec($this->ch);
		$this->requestHeaders = curl_getinfo($this->ch);

		
		curl_close($this->ch);
	}

	
	/**
	 * Set option
	 * https://www.php.net/manual/en/function.curl-setopt.php
	 * @param int   $key
	 * @param mixed $value
	 * @return void
	 */
	public function setOption(int $key, mixed $value): void
	{
		$this->options[$key] = $value;
	}

	/**
	 * Get request
	 * @return void
	 */
	protected function get(): void 
	{
		$this->createRequest();
	}

	/**
	 * Post request
	 * @return void
	 */
	protected function post(): void 
	{	
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		$this->createRequest();
	}

	/**
	 * Put request
	 * @return void
	 */
	protected function put(): void 
	{
		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestData);
		rewind($fh);
		
		curl_setopt($this->ch, CURLOPT_INFILE, $fh);
		curl_setopt($this->ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($this->ch, CURLOPT_PUT, true);
		$this->createRequest();

		fclose($fh);
	}

	/**
	 * Path request
	 * @return void
	 */
	protected function patch(): void 
	{
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		$this->setHeader("content-type", "application/json-patch+json");
		$this->createRequest();
	}

	/**
	 * Delete request
	 * @return void
	 */
	protected function delete(): void 
	{
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$this->createRequest();
	}

	/**
	 * Will build you options
	 * @return void
	 */
	private function buildOptions(): void
	{
		foreach($this->options as $i => $val) {
			if(!is_int($i)) throw new ClientException("The options key needs to be an integer!", 1);
			curl_setopt($this->ch, $i, $val);
		}
	}
}
