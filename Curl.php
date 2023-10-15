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

namespace Request;


class Curl {

	private $url;
	private $verb;
	private $requestData;
	private $requestLength;

	private $username;
	private $password;
	private $basicAuth;
	private $port;
	private $ip;

	private $httpVersion;
	private $headerArr = array();
	private $httpAuth = CURLAUTH_DIGEST;
	private $timeout = 10;
	private $accept = "application/json";
	private $sslVersion;
	private $verifySSLHostName;
	private $verifySSLPeer;
	private $ch;

	private $response;
	private $meta;

	
	function __construct(string $url, string $verb = 'GET', array|string|null $requestBody = NULL) {
		$this->url = $url;
		$this->verb = $verb;
		if(!is_null($requestBody)) $this->setRequestBody($requestBody);
	}

	/**
	 * Pass on headers as array
	 * @param array $arr
	 * @return self 
	 */
	public function setHeaders(array $arr): self 
	{
		foreach($arr as $key => $value) $this->setHeader($key, $value);
		return $this;
	}

	/**
	 * Pass on headers
	 * @param key 	$key	Header name
	 * @param value $value 	Header value
	 * @return self 
	 */
	public function setHeader(string $key, string $value): self 
	{
		$this->headerArr[$key] = $value;
		return $this;
	}

	/**
	 * Set request body
	 * @param array|string 	[offset => 2, limit => 30] || "offset=2&limit=30"
	 * @return self 
	 */
	public function setRequestBody(array|string $requestBody): self 
	{
		$this->requestData = $this->buildPostBody($requestBody);
		$this->requestLength = strlen($this->requestData);
		return $this;
	}

	/**
	 * HTTP authentication method
	 * @param int $authType CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_GSSNEGOTIATE, CURLAUTH_NTLM, CURLAUTH_AWS_SIGV4, CURLAUTH_ANY, and CURLAUTH_ANYSAFE. 
	 * @return self 
	 */
	public function setHttpAuthMethod(int $authType): self 
	{
		$this->httpAuth = int $authType;
		return $this;
	}

	/**
	 * HTTP authentication username
	 * @param string $user
	 * @return self 
	 */
	public function setUsername(string $user): self 
	{
		$this->username = $user;
		return $this;
	}

	/**
	 * HTTP authentication password
	 * @param string $pass
	 * @return self 
	 */
	public function setPassword(string $pass): self 
	{
		$this->password = $pass;
		return $this;
	}

	/**
	 * Enable Basic authentication
	 * @return self 
	 */
	public function enableBasicAuth(): self 
	{
		$this->basicAuth = base64_encode("{$this->username}:{$this->password}");
		return $this;
	}

	/**
	 * Set accept
	 * @param string $accept
	 * @return self 
	 */
	public function setAccept(string $accept): self 
	{
		$this->accept = $accept;
		return $this;
	}

	/**
	 * Set timeout
	 * @param int $timeout
	 * @return self 
	 */
	public function setTimeout(int $timeout): self 
	{
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * Set port number
	 * @param int $port
	 * @return self 
	 */
	public function setPort(int $port): self 
	{
		$this->port = $port;
		return $this;
	}

	/**
	 * Set IP number
	 * @param int $ip
	 * @return self 
	 */
	public function setIp(string $ip): self 
	{
		$this->ip = $ip;
		return $this;
	}

	/**
	 * Verify SSL Host names
	 * E.g. You should avoid settings this to false and only if you do not have any other option.
	 * @param bool $verify default is true
	 * @return self 
	 */
	public function enableVerifySSL(bool $verify): self 
	{
		$this->enableVerifySSLHostName($verify);
		$this->enableVerifySSLPeer($verify);
		return $this;
	}

	/**
	 * Verify SSL Host names
	 * @param bool $verify default is true
	 * @return self 
	 */
	public function enableVerifySSLHostName(bool $verify): self 
	{
		$this->verifySSLHostName = ($verify) ? 2 : 0;
		return $this;
	}

	/**
	 * Verify SSL Peer
	 * @param bool $verify default is true
	 * @return self 
	 */
	public function enableVerifySSLPeer(bool $verify): self 
	{
		$this->verifySSLPeer = $verify;
		return $this;
	}

	/**
	 * Force SSL version
	 * This method MIGHT change ssl version, it depends on how many TLS is enable in openssl, most likley will TLS1.2 only be enabled.
	 * IF you have SSH access you can find out and edit this right on the server.
	 * @param  boolean $sslversion You can use default constants: CURLsslVersion_TLSv1_0, CURLsslVersion_TLSv1_1, CURLsslVersion_TLSv1_2, CURLsslVersion_TLSv1_3
	*/
	public function setSSLVersion(int $version): self 
	{
		$this->sslVersion = $version;
		return $this;
	}

	/**
	 * Set expected HTTP version
	 * CURL_HTTP_VERSION_NONE, CURL_HTTP_VERSION_1_0, CURL_HTTP_VERSION_1_1, CURL_HTTP_VERSION_2_0, 
	 * CURL_HTTP_VERSION_2, CURL_HTTP_VERSION_2_0, CURL_HTTP_VERSION_2TLS, CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE
	 * @param bool $verify default is true
	 * @return self 
	 */
	public function setHttpVersion(int $version): self 
	{
		$this->httpVersion = $version;
		return $this;
	}

	/**
	 * Execute Request
	 * @return string|null response
	 */
	public function execute(): ?string 
	{
		if(in_array('curl', get_loaded_extensions())) {
			if(is_null($this->ch)) {

				$this->ch = curl_init();
				$this->options();

				try {
					switch (strtoupper($this->verb)) {
						case 'GET':
							$this->createRequest();
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
							throw new \InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
					}

					return $this->response;
					
				} catch (\InvalidArgumentException $e) {
					curl_close($this->ch);
					throw $e;
				
				} catch (\Exception $e) {
					curl_close($this->ch);
					throw $e;
				}
			}

		} else {
			throw new \Exception("Curl is missing and needs to be enabled.", 1);
		}


		return NULL;
	}


	/**
	 * Execute Request
	 * @return string|null response
	 */
	public function getResponse(): string 
	{
		return $this->response;
	}

	public function getMeta() 
	{
		return $this->meta;
	}

	protected function post(): void 
	{	
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		$this->createRequest($this->ch);
	}

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

	protected function patch(): void 
	{
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $this->requestData);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
		$this->setHeader("content-type", "application/json-patch+json");
		$this->createRequest($this->ch);
	}

	protected function delete(): void 
	{
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$this->createRequest($this->ch);
	}


	private function createRequest(): void 
	{
		$this->response = curl_exec($this->ch);
		$this->meta = curl_getinfo($this->ch);
		curl_close($this->ch);
	}

	private function options(): void 
	{
		// Auth (login)
		if(!is_null($this->username) && !is_null($this->password)) {
			if(!is_null($this->basicAuth)) {
				$this->setHeader("Authorization", "Basic {$this->basicAuth}");
			} else {
				curl_setopt($this->ch, CURLOPThttpAuth, $this->httpAuth);
				curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
			}
		}

		if(!is_null($this->httpVersion)) curl_setopt($this->ch, CURLOPT_HTTP_VERSION, $this->httpVersion);

		// Auth (port, IP)
		if(!is_null($this->port)) curl_setopt($this->ch, CURLOPTport, $this->port);
		if(!is_null($this->ip)) curl_setopt($this->ch, CURLOPT_INTERFACE, $this->ip);

		curl_setopt($this->ch, CURLOPTtimeout, $this->timeout);
		curl_setopt($this->ch, CURLOPT_URL, $this->url);

		if(!is_null($this->sslVersion)) {
			curl_setopt($this->ch, CURLOPTsslVersion, $this->sslVersion);
		}

		if(!is_null($this->verifySSLHostName)) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, $this->verifySSLHostName);
		}

		if(!is_null($this->verifySSLPeer)) {
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSLPeer);
		}

		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);

		$this->setHeader("Accept", $this->accept);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->buildHeaders());
	}

	private function buildHeaders(): array 
	{
		$arr = array();
		foreach($this->headerArr as $key => $value) {
			$arr[] = "{$key}: {$value}";
		}
		return $arr;
	}

	private function buildPostBody(array|string $data): string 
	{
		if(is_array($arr)) {
			return http_build_query($data, '', '&');
		}
		return $data;		
	}
	
}
