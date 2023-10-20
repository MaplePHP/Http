<?php
declare(strict_types=1);

namespace PHPFuse\Http;

use PHPFuse\Http\Interfaces\RequestInterface;
use PHPFuse\Http\Interfaces\ResponseInterface;
use PHPFuse\Http\Interfaces\ClientInterface;
use PHPFuse\Http\Interfaces\StreamInterface;

use PHPFuse\Http\Exceptions\ClientException;
use PHPFuse\Http\Exceptions\RequestException;
use PHPFuse\Http\Exceptions\NetworkException;
use InvalidArgumentException;

class Client implements ClientInterface {

	
	const DEFAULT_TIMEOUT = 30;
	const DEFAULT_AUTH = CURLAUTH_DIGEST;

	private $options;
	private $ch;
	
	private $requestData;
	private $requestDataLength;
	private $requestResponse;
	private $requestMeta;

	function __construct(array $options = []) {
		$this->options = $options;
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
	 * Has option
	 * https://www.php.net/manual/en/function.curl-setopt.php
	 * @param int   $key
	 * @return void
	 */
	public function hasOption(int $key): bool
	{
		return (bool)(isset($this->options[$key]));
	}

	/**
	 * Sends a PSR-7 request and returns a PSR-7 response.
	 * @param  RequestInterface $request
	 * @return ResponseInterface
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface 
	{
		$this->requestData = (string)$request->getBody();
		$this->requestDataLength = strlen($this->requestData);
		$this->prepareRequest($request);

		try {

			if(!extension_loaded('curl')) throw new InvalidArgumentException('You need to enable CURL on your server.');

			// Init curl request
			$this->ch = curl_init();
			$this->buildOptions();
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
					$request = $this->patch($request);
				break;
				case 'DELETE':
					$this->delete();
				break;
				default:
					throw new InvalidArgumentException('The requesr method ('.$request->getMethod().') is not supported.');
			}

			// Execute request
			$this->createRequest();	

			// Close curl request
			curl_close($this->ch);		

		} catch (InvalidArgumentException $e) {
			throw new RequestException($e->getMessage(), 1);

		} catch (NetworkException $e) {
			throw $e;
		}

		// Retrive the body
		return $this->createResponse();
	}

	/**
	 * This will open init the request
	 * @param  RequestInterface $request
	 * @return void
	 */
	protected function prepareRequest(RequestInterface $request): void 
	{
		$this->setOption(CURLOPT_URL, $request->getUri()->getUri());
		$this->setOption(CURLOPT_RETURNTRANSFER, true);

		// Default auth option if get user name
		if(!$this->hasOption(CURLOPT_HTTPAUTH) && !is_null($request->getUri()->getPart("user"))) {
			$this->setOption(CURLOPT_HTTPAUTH, static::DEFAULT_AUTH);
		}

		if(!$this->hasOption(CURLOPT_TIMEOUT)) {
			$this->setOption(CURLOPT_TIMEOUT, static::DEFAULT_TIMEOUT);
		}
	}

	protected function createResponse(): ResponseInterface
	{
		$stream = new Stream(Stream::TEMP);
		$stream->write($this->requestResponse);
		if(!$stream->isSeekable()) {
			throw new RequestExceptionInterface("Request body is not seekable", 1);
		}
		$stream->seek(0);

		return new Response($stream);
	}

	/**
	 * Main request. This will be used for all the request.
	 * @return void
	 */
	final protected function createRequest(): void 
	{
		$this->requestResponse = curl_exec($this->ch);
		if($this->requestResponse === false) throw new NetworkException(curl_error($this->ch), 1);
		$this->requestMeta = curl_getinfo($this->ch);
	}

	/**
	 * Get request
	 * @return void
	 */
	protected function get(): void 
	{
		// Is empty placeholder at the moment, does not need any more code fo it to work buy..
		// you could extend and add your own functionality to it if you want
	}

	/**
	 * Post request
	 * @return void
	 */
	protected function post(): void 
	{	
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_POST, 1);
	}

	/**
	 * Put request
	 * @return void
	 */
	protected function put(): void 
	{
		curl_setopt($this->ch, CURLOPT_INFILE, $this->createParsedBody()->getResource());
		curl_setopt($this->ch, CURLOPT_INFILESIZE, $this->requestDataLength);
		curl_setopt($this->ch, CURLOPT_PUT, true);
	}

	/**
	 * Path request
	 * @return void
	 */
	protected function patch(RequestInterface $request): RequestInterface 
	{
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		$request = $request->withHeader("content-type", "application/json-patch+json");
		return $request;
	}

	/**
	 * Delete request
	 * @return void
	 */
	protected function delete(): void 
	{
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
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

	/**
	 * Build the headers
	 * @return void
	 */
	private function buildHeaders(RequestInterface $request): void {
		$data = array();
		foreach($request->getHeaders() as $name => $val) {
			$data[] = "{$name}: ".$request->getHeaderLine($name);
		}
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $data);
	}

	/**
	 * Parsed body can be used in e.g. put method
	 * @return StreamInterface
	 */
	private function createParsedBody(): StreamInterface
	{
		$stream = new Stream('php://memory', 'rw');
		$stream->write($this->requestData);
		$stream->rewind();
		return $stream;
	}
}
