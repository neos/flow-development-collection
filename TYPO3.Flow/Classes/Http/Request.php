<?php
namespace TYPO3\FLOW3\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Mvc\ActionRequest;

/**
 * Represents a HTTP request
 *
 * @api
 */
class Request {

	/**
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $uri;

	/**
	 * @var \TYPO3\FLOW3\Http\Uri
	 */
	protected $baseUri;

	/**
	 * @var \TYPO3\FLOW3\Http\Headers
	 */
	protected $headers;

	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @var array<\TYPO3\FLOW3\Http\Cookie>
	 */
	protected $cookies;

	/**
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 * @FLOW3\Inject
	 */
	protected $session;

	/**
	 * Data similar to that which is typically provided by $_SERVER
	 *
	 * @var array
	 */
	protected $server;

	/**
	 * Constructs a new Request object based on the given environment data.
	 *
	 * @param array $get Data similar to that which is typically provided by $_GET
	 * @param array $post Data similar to that which is typically provided by $_POST
	 * @param array $cookie Data similar to that which is typically provided by $_COOKIE
	 * @param array $files Data similar to that which is typically provided by $_FILES
	 * @param array $server Data similar to that which is typically provided by $_SERVER
	 * @see create()
	 * @see createFromEnvironment()
	 * @api
	 */
	public function __construct(array $get, array $post, array $cookie, array $files, array $server) {
		$this->headers = Headers::createFromServer($server);
		$this->setMethod(isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET');
		$protocol = (isset($server['SSL_SESSION_ID']) || (isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || strcmp($server['HTTPS'], '1') === 0))) ? 'https' : 'http';
		$this->uri = new Uri($protocol . '://' . $server['HTTP_HOST'] . str_replace('/index.php' , '', $server['REQUEST_URI']));
		$this->server = $server;
		$this->arguments = $this->buildUnifiedArguments($get, $post, $files);
	}

	/**
	 * Creates a new Request object from the given data.
	 *
	 * @param \TYPO3\FLOW3\Http\Uri $uri The request URI
	 * @param string $method Request method, for example "GET"
	 * @param array $arguments
	 * @param array $cookies
	 * @param array $files
	 * @param array $server
	 * @return \TYPO3\FLOW3\Http\Request
	 * @api
	 */
	static public function create(Uri $uri, $method = 'GET', array $arguments = array(), array $cookies = array(), array $files = array(), array $server = array()) {
		if (!in_array($method, array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'))) {
			throw new \InvalidArgumentException(sprintf('Invalid method "%s".', $method), 1326706916);
		}

		$get = $uri->getArguments();
		$post = $arguments;

		$defaultServerEnvironment = array(
			'HTTP_USER_AGENT' => 'FLOW3/' . FLOW3_VERSION_BRANCH . '.x',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
			'HTTP_ACCEPT_CHARSET' => 'utf-8',
			'HTTP_HOST' => $uri->getHost(),
			'SERVER_NAME' => $uri->getHost(),
			'SERVER_ADDR' => '127.0.0.1',
			'SERVER_PORT' => $uri->getPort() ?: 80,
			'REMOTE_ADDR' => '127.0.0.1',
			'SCRIPT_FILENAME' => FLOW3_PATH_WEB . 'index.php',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
		);

		if ($uri->getScheme() === 'https') {
			$defaultServerEnvironment['HTTPS'] = 'on';
			$defaultServerEnvironment['SERVER_PORT'] = 443;
		}

		if (in_array($method, array('POST', 'PUT', 'DELETE'))) {
			$defaultServerEnvironment['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
		}

		$query = $uri->getQuery();
		$fragment = $uri->getFragment();
		$overrideValues = array(
			'REQUEST_URI' => $uri->getPath() . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : ''),
			'REQUEST_METHOD' => $method,
			'QUERY_STRING' => $query
		);
		$server = array_replace($defaultServerEnvironment, $server, $overrideValues);

		return new static($get, $post, $cookies, $files, $server);
	}

	/**
	 * Considers the environment information found in PHP's superglobals and FLOW3's
	 * environment configuration and creates a new instance of this Request class
	 * matching that data.
	 *
	 * @return \TYPO3\FLOW3\Http\Request
	 * @api
	 */
	static public function createFromEnvironment() {
		return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
	}

	/**
	 * Creates a new Action Request request as a sub request to this HTTP request.
	 * Maps the arguments of this request to the new Action Request.
	 *
	 * @return \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	public function createActionRequest() {
		$actionRequest = new ActionRequest($this);
		$actionRequest->setArguments($this->arguments);
		return $actionRequest;
	}

	/**
	 * Returns the request URI
	 *
	 * @return \TYPO3\FLOW3\Http\Uri
	 * @api
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * Returns the detected base URI
	 *
	 * @return \TYPO3\FLOW3\Http\Uri
	 * @api
	 */
	public function getBaseUri() {
		if ($this->baseUri === NULL) {
			$this->detectBaseUri();
		}
		return $this->baseUri;
	}

	/**
	 * Indicates if this request has been received through a secure channel.
	 *
	 * @return boolean
	 * @api
	 */
	public function isSecure() {
		return ($this->uri->getScheme() === 'https' || $this->headers->has('X-Forwarded-Proto'));
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method The request method, for example "GET".
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setMethod($method) {
		if (!in_array($method, array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'))) {
			throw new \InvalidArgumentException(sprintf('Invalid method "%s".', $method), 1326445656);
		}
		$this->method = $method;
	}

	/**
	 * Returns the request method
	 *
	 * @return string The request method
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Tells if the request method is "safe", that is, it is expected to not take any
	 * other action than retrieval. This should the case with "GET" and "HEAD" requests.
	 *
	 * @return boolean
	 * @api
	 */
	public function isMethodSafe() {
		return (in_array($this->method, array('GET', 'HEAD')));
	}

	/**
	 * Returns the unified arguments of this request.
	 *
	 * GET, POST and PUT arguments, as well es uploaded files, are merged into a whole
	 * array of arguments.
	 *
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Returns the HTTP headers of this request
	 *
	 * @return \TYPO3\FLOW3\Http\Headers
	 * @api
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Returns the relative path (ie. relative to the web root) and name of the
	 * script as it was accessed through the webserver.
	 *
	 * @return string Relative path and name of the PHP script as accessed through the web
	 * @api
	 */
	public function getScriptRequestPathAndFilename() {
		if (isset($this->server['SCRIPT_NAME'])) {
			return $this->server['SCRIPT_NAME'];
		}
		if (isset($this->server['ORIG_SCRIPT_NAME'])) {
			return $this->server['ORIG_SCRIPT_NAME'];
		}
		return '';
	}

	/**
	 * Returns the relative path (ie. relative to the web root) to the script as
	 * it was accessed through the webserver.
	 *
	 * @return string Relative path to the PHP script as accessed through the web
	 * @api
	 */
	public function getScriptRequestPath() {
		$requestPathSegments = explode('/', $this->getScriptRequestPathAndFilename());
		array_pop($requestPathSegments);
		return implode('/', $requestPathSegments) . '/';
	}

	/**
	 * Tries to detect the base URI of request.
	 *
	 * @return void
	 */
	protected function detectBaseUri() {
		$this->baseUri = clone $this->uri;
		$this->baseUri->setQuery(NULL);
		$this->baseUri->setFragment(NULL);
		$this->baseUri->setPath($this->getScriptRequestPath());
	}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @return void
	 */
	protected function buildUnifiedArguments(array $getArguments, array $postArguments, array $uploadArguments) {
		$arguments = $getArguments;
		if ($this->method === 'POST' || $this->method === 'PUT') {
			$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $postArguments);
			$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $uploadArguments);
		}
		return $arguments;
	}

}

?>