<?php
namespace TYPO3\FLOW3\Utility;

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

/**
 * Abstraction methods which return system environment variables regardless
 * of server OS, CGI/MODULE version etc. Basically they are the _SERVER
 * variables in most cases.
 *
 * This class should be used instead of the $_SERVER/ENV_VARS to get reliable
 * values for all situations.
 *
 * WARNING: Don't try to subclass this class except for use in unit tests. The
 * superglobal replacement will lead to unexpected behavior (on your side).
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class Environment {

	/**
	 * @var string
	 */
	protected $context = '';

	/**
	 * A local copy of the _SERVER super global
	 * @var array
	 */
	protected $SERVER;

	/**
	 * A local copy of the _GET super global
	 * @var array
	 */
	protected $GET;

	/**
	 * A local copy of the _POST super global
	 * @var array
	 */
	protected $POST;

	/**
	 * A local copy of the _FILES super global
	 * @var array
	 */
	protected $FILES;

	/**
	 * A lower case string specifying the currently used Server API. See php_sapi_name()/PHP_SAPI for possible values
	 * @var string
	 */
	protected $SAPIName = PHP_SAPI;

	/**
	 * The base path of $temporaryDirectory. This property can (and should) be set from outside.
	 * @var string
	 */
	protected $temporaryDirectoryBase;

	/**
	 * @var string
	 */
	protected $temporaryDirectory = NULL;

	/**
	 * @var \TYPO3\FLOW3\Property\DataType\Uri
	 */
	protected $baseUri;

	/**
	 * Initializes the environment instance. Copies the superglobals $_SERVER,
	 * $_GET, $_POST, $_FILES to local variables.
	 *
	 * @param string $context The FLOW3 context
	 * @return void
	 */
	public function __construct($context) {
		$this->context = $context;

		$this->SERVER = $_SERVER;
		$this->GET = $_GET;
		$this->POST = $_POST;
		$this->FILES = $this->untangleFilesArray($_FILES);
	}

	/**
	 * Sets the base path of the temporary directory
	 *
	 * @param string $temporaryDirectoryBase Base path of the temporary directory, with trailing slash
	 * @return void
	 */
	public function setTemporaryDirectoryBase($temporaryDirectoryBase) {
		$this->temporaryDirectoryBase = $temporaryDirectoryBase;
		$this->temporaryDirectory = NULL;
	}

	/**
	 * Set the base URI
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $baseUri The base URI or NULL if the base URI should be detected automatically
	 * @return void
	 */
	public function setBaseUri(\TYPO3\FLOW3\Property\DataType\Uri $baseUri = NULL) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the HTTP Host
	 *
	 * @return string The HTTP Host as found in _SERVER[HTTP_HOST]
	 * @api
	 */
	public function getHTTPHost() {
		return isset($this->SERVER['HTTP_HOST']) ? $this->SERVER['HTTP_HOST'] : NULL;
	}

	/**
	 * Returns the HTTP referer
	 *
	 * @return string The HTTP referer as found in _SERVER[HTTP_REFERER]
	 * @api
	 */
	public function getHTTPReferer() {
		return isset($this->SERVER['HTTP_REFERER']) ? $this->SERVER['HTTP_REFERER'] : NULL;
	}

	/**
	 * Returns the HTTP user agent
	 *
	 * @return string The HTTP user agent as found in _SERVER[HTTP_USER_AGENT]
	 * @api
	 */
	public function getHTTPUserAgent() {
		return isset($this->SERVER['HTTP_USER_AGENT']) ? $this->SERVER['HTTP_USER_AGENT'] : NULL;
	}

	/**
	 * Returns the HTTP Accept string
	 *
	 * @return string The HTTP Accept string as found in _SERVER[HTTP_ACCEPT]
	 * @api
	 */
	public function getHTTPAccept() {
		return isset($this->SERVER['HTTP_ACCEPT']) ? $this->SERVER['HTTP_ACCEPT'] : NULL;
	}

	/**
	 * Returns all HTTP headers set for this request by converting them from
	 * HTTP_* environment variables. E.g. "HTTP_ACCEPT" will be available under
	 * "Accept" and "HTTP_CUSTOM_HEADER" as "Custom-Header".
	 *
	 * Note that this doesn't give you the raw headers in any case. For example
	 * "HTTP_SOAPACTION" will be available as "Soapaction" and not under the
	 * original mixed-case name "SOAPAction".
	 *
	 * @return array
	 * @api
	 */
	public function getRequestHeaders() {
		$headers = array();
		foreach($this->SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') === 0) {
				$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
				$headers[$key] = $value;
			} elseif (strpos($key, 'PHP_AUTH_') === 0) {
				$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 9)))));
				$headers[$key] = $value;
			} elseif ($key == 'REDIRECT_REMOTE_AUTHORIZATION') {
				$authorizationData = base64_decode($value);
				list($headers['User'], $headers['Pw']) = explode(':', $authorizationData);
			}
		}
		return $headers;
	}

	/**
	 * Returns a sorted list (most important first) of accepted formats (ie. file extensions) as
	 * defined in the browser's Accept header.
	 *
	 * Note that most browser do not properly use the Accept header and content negotiation based
	 * on this information mostly makes sense when we can expect the client to explicitly ask for
	 * a certain content type, for example in an AJAX call.
	 *
	 * @return array A sorted array with filename extensions
	 * @api
	 *
	 */
	public function getAcceptedFormats() {
		$acceptHeader = $this->getHTTPAccept();
		$parseAcceptType = function($acceptType) {
			$typeAndQuality = preg_split('/;\s*q=/', $acceptType);
			return array($typeAndQuality[0], (isset($typeAndQuality[1]) ? (float)$typeAndQuality[1] : (float)1));
		};

		$acceptedTypes = array_map($parseAcceptType, preg_split('/,\s*/', $acceptHeader));
		$flattenedAcceptedTypes = array();
		foreach ($acceptedTypes as $typeAndQuality) {
			$flattenedAcceptedTypes[$typeAndQuality[0]] = $typeAndQuality[1];
		}
		arsort($flattenedAcceptedTypes);

		$acceptedFormats = array();
		foreach ($flattenedAcceptedTypes as $mimeType => $quality) {
			$format = \TYPO3\FLOW3\Utility\FileTypes::getFilenameExtensionFromMimeType($mimeType);
			if ($format !== '') {
				$acceptedFormats[$format] = TRUE;
			}
		}
		return array_keys($acceptedFormats);
	}

	/**
	 * Returns the HTTP accept language
	 *
	 * @return string The HTTP accept language as found in _SERVER[HTTP_ACCEPT_LANGUAGE]
	 * @api
	 */
	public function getHTTPAcceptLanguage() {
		return isset($this->SERVER['HTTP_ACCEPT_LANGUAGE']) ? $this->SERVER['HTTP_ACCEPT_LANGUAGE'] : NULL;
	}

	/**
	 * Returns the remote address
	 *
	 * @return string The remote address as found in _SERVER[REMOTE_ADDR]
	 * @api
	 */
	public function getRemoteAddress() {
		return isset($this->SERVER['REMOTE_ADDR']) ? $this->SERVER['REMOTE_ADDR'] : NULL;
	}

	/**
	 * Returns the remote host
	 *
	 * @return string The remote host as found in _SERVER[REMOTE_HOST]
	 * @api
	 */
	public function getRemoteHost() {
		return isset($this->SERVER['REMOTE_HOST']) ? $this->SERVER['REMOTE_HOST'] : NULL;
	}

	/**
	 * Returns the protocol (http or https) used in the request
	 *
	 * @return string The used protol, either http or https
	 * @api
	 */
	public function getRequestProtocol() {
		$protocol = 'http';
		if (isset($this->SERVER['SSL_SESSION_ID'])) {
			$protocol = 'https';
		} elseif (isset($this->SERVER['HTTPS'])) {
			if ($this->SERVER['HTTPS'] === 'on' || strcmp($this->SERVER['HTTPS'], '1') === 0) {
				$protocol = 'https';
			}
		}
		return $protocol;
	}

	/**
	 * Returns the Full URI of the request, including the correct protocol, host and path.
	 * Note that the past in this URI _contains_ the full base URI which means that its
	 * not always a relative path to the base URI.
	 *
	 * The script name "index.php" will be removed if it exists.
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri The request URI
	 * @api
	 */
	public function getRequestUri() {
		if ($this->SAPIName === 'cli') {
			return FALSE;
		}

		return new \TYPO3\FLOW3\Property\DataType\Uri($this->getRequestProtocol() . '://' . $this->getHTTPHost() . str_replace('/index.php' , '', $this->SERVER['REQUEST_URI']));
	}

	/**
	 * Returns the current base URI which is the root FLOW3's relative URIs.
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri The base URI
	 */
	public function getBaseUri() {
		if ($this->baseUri === NULL) {
			$this->detectBaseUri();
		}
		return $this->baseUri;
	}

	/**
	 * Returns the full, absolute path and the file name of the executed PHP
	 * file (on the server, not as in the request URI).
	 *
	 * @return string The full path and file name of the PHP script
	 * @api
	 */
	public function getScriptPathAndFilename() {
		return \TYPO3\FLOW3\Utility\Files::getUnixStylePath($this->SERVER['SCRIPT_FILENAME']);
	}

	/**
	 * Returns the relative path (ie. relative to the web root) and name of the
	 * script as it was accessed through the webserver.
	 *
	 * @return string Relative path and name of the PHP script as accessed through the web
	 * @api
	 */
	public function getScriptRequestPathAndFilename() {
		if (isset($this->SERVER['SCRIPT_NAME'])) return $this->SERVER['SCRIPT_NAME'];
		if (isset($this->SERVER['ORIG_SCRIPT_NAME'])) return $this->SERVER['ORIG_SCRIPT_NAME'];
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
	 * Returns the request method as found in the SERVER environment.
	 * Examples: "GET", "POST", 'DELETE' ...
	 *
	 * @return string The request method
	 * @api
	 */
	public function getRequestMethod() {
		return (isset($this->SERVER['REQUEST_METHOD'])) ? $this->SERVER['REQUEST_METHOD'] : NULL;
	}

	/**
	 * Returns the number of command line arguments, including the program name!
	 *
	 * @return integer The number of command line arguments passed to the main script.
	 * @api
	 */
	public function getCommandLineArgumentCount() {
		return isset($this->SERVER['argc']) ? $this->SERVER['argc'] : 0;
	}

	/**
	 * Returns an array of arguments passed through the command line.
	 * Only makes sense in CLI mode of course.
	 *
	 * @return array The command line arguments (including program name), if any
	 * @api
	 */
	public function getCommandLineArguments() {
		return isset($this->SERVER['argv']) ? $this->SERVER['argv'] : array();
	}

	/**
	 * Returns a lowercase string which identifies the currently used
	 * Server API (SAPI).
	 *
	 * Common SAPIS are "apache", "isapi", "cli", "cgi" etc.
	 *
	 * @return string A lower case string identifying the SAPI used
	 * @see php_sapi_name()/PHP_SAPI, getNormalizedSAPIName()
	 * @api
	 */
	public function getSAPIName() {
		return $this->SAPIName;
	}

	/**
	 * Returns the GET arguments array from the _GET superglobal
	 *
	 * @return array Unfiltered, raw, insecure, tainted GET arguments
	 * @api
	 */
	public function getRawGetArguments() {
		return $this->GET;
	}

	/**
	 * Returns the POST arguments array from the _POST superglobal
	 *
	 * @return array Unfiltered, raw, insecure, tainted POST arguments
	 * @api
	 */
	public function getRawPostArguments() {
		return $this->POST;
	}

	/**
	 * Returns the FILES arguments array from the _FILES superglobal. It applies
	 * some helpful processing to the data before it is returned, so that arrays
	 * of uploaded files are "deinterweaved" to be more as one would expect.
	 *
	 * Nested array uploads are untangled in a smiliar way, so that you always
	 * get a nice and clean array of the form
	 *  argumentName => array(name, tmp_name, error, size, type)
	 * where argumentName can be a nested array if needed:
	 *  form field name "foo[bar]" leads to
	 *  argumentName foo => array(bar => array(...))
	 *
	 * @return array Unfiltered, raw, insecure, tainted files
	 * @api
	 */
	public function getUploadedFiles() {
		return $this->FILES;
	}

	/**
	 * Returns the unfiltered, raw, unchecked SERVER superglobal
	 * If available, please always use an alternative method of this API.
	 *
	 * @return array Unfiltered, raw, insecure, tainted SERVER environment
	 * @api
	 */
	public function getRawServerEnvironment() {
		return $this->SERVER;
	}

	/**
	 * Returns the full path to FLOW3's temporary directory.
	 *
	 * @return string Path to PHP's temporary directory
	 * @api
	 */
	public function getPathToTemporaryDirectory() {
		if ($this->temporaryDirectory !== NULL) return $this->temporaryDirectory;

		try {
			$this->temporaryDirectory = $this->createTemporaryDirectory($this->temporaryDirectoryBase);
		} catch (\TYPO3\FLOW3\Utility\Exception $exception) {
			$fallBackTemporaryDirectoryBase = (DIRECTORY_SEPARATOR === '/') ? '/tmp' : '\\WINDOWS\\TEMP';
			$this->temporaryDirectory = $this->createTemporaryDirectory($fallBackTemporaryDirectoryBase);
		}
		return $this->temporaryDirectory;
	}

	/**
	 * Retrieves the maximum path lenght that is valid in the current environment.
	 *
	 * @return integer The maximum available path length
	 */
	public function getMaximumPathLength() {
		return PHP_MAXPATHLEN;
	}

	/**
	 * Whether or not URL rewriting is enabled.
	 *
	 * @return boolean
	 */
	public function isRewriteEnabled() {
		if (getenv('REDIRECT_FLOW3_REWRITEURLS')) {
			return TRUE;
		}
		return (boolean)getenv('FLOW3_REWRITEURLS');
	}

	/**
	 * Tries to detect the base URI of request.
	 *
	 * @return void
	 */
	protected function detectBaseUri() {
		$requestUri = $this->getRequestUri();
		if ($requestUri !== FALSE) {
			$this->baseUri = $requestUri;
			$this->baseUri->setQuery(NULL);
			$this->baseUri->setFragment(NULL);
			$this->baseUri->setPath($this->getScriptRequestPath());
		}
	}

	/**
	 * Transforms the convoluted _FILES superglobal into a manageable form.
	 *
	 * @param array $convolutedFiles The _FILES superglobal
	 * @return array Untangled files
	 */
	protected function untangleFilesArray(array $convolutedFiles) {
		$untangledFiles = array();

		$fieldPaths = array();
		foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
			if (!is_array($fieldInformation['error'])) {
				$fieldPaths[] = array($firstLevelFieldName);
			} else {
				$newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
				array_walk($newFieldPaths,
					function(&$value, $key) {
						$value = explode('/', $value);
					}
				);
				$fieldPaths = array_merge($fieldPaths, $newFieldPaths);
			}
		}

		foreach ($fieldPaths as $fieldPath) {
			if (count($fieldPath) === 1) {
				$fileInformation = $convolutedFiles[$fieldPath{0}];
			} else {
				$fileInformation = array();
				foreach ($convolutedFiles[$fieldPath{0}] as $key => $subStructure) {
					$fileInformation[$key] = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($subStructure, array_slice($fieldPath, 1));
				}
			}
			$untangledFiles = \TYPO3\FLOW3\Utility\Arrays::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
		}
		return $untangledFiles;
	}

	/**
	 * Returns and array of all possibles "field paths" for the given array.
	 *
	 * @param array $structure The array to walk through
	 * @param string $firstLevelFieldName
	 * @return array An array of paths (as strings) in the format "key1/key2/key3" ...
	 */
	protected function calculateFieldPaths(array $structure, $firstLevelFieldName = NULL) {
		$fieldPaths = array();
		if (is_array($structure)) {
			foreach ($structure as $key => $subStructure) {
				$fieldPath = ($firstLevelFieldName !== NULL ? $firstLevelFieldName . '/' : '') . $key;
				if (is_array($subStructure)) {
					foreach($this->calculateFieldPaths($subStructure) as $subFieldPath) {
						$fieldPaths[] = $fieldPath . '/' . $subFieldPath;
					}
				} else {
					$fieldPaths[] = $fieldPath;
				}
			}
		}
		return $fieldPaths;
	}

	/**
	 * Creates FLOW3's temporary directory - or at least asserts that it exists and is
	 * writable.
	 *
	 * @param string $temporaryDirectoryBase Full path to the base for the temporary directory
	 * @return string The full path to the temporary directory
	 * @throws \TYPO3\FLOW3\Utility\Exception if the temporary directory could not be created or is not writable
	 */
	protected function createTemporaryDirectory($temporaryDirectoryBase) {
		$temporaryDirectoryBase = \TYPO3\FLOW3\Utility\Files::getUnixStylePath($temporaryDirectoryBase);
		if (substr($temporaryDirectoryBase, -1, 1) !== '/') $temporaryDirectoryBase .= '/';
		$temporaryDirectory = $temporaryDirectoryBase . $this->context . '/';

		if (!is_dir($temporaryDirectory) && !is_link($temporaryDirectory)) {
			try {
				\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($temporaryDirectory);
			} catch (\TYPO3\FLOW3\Error\Exception $exception) {
			}
		}

		if (!is_writable($temporaryDirectory)) {
			throw new \TYPO3\FLOW3\Utility\Exception('The temporary directory "' . $temporaryDirectory . '" could not be created or is not writable. Please make this directory writable or define another temporary directory by setting the respective system environment variable (eg. TMPDIR) or defining it in the FLOW3 settings.', 1216287176);
		}

		return $temporaryDirectory;
	}
}
?>