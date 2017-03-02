<?php
namespace Neos\Flow\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Utility\Arrays;
use Neos\Utility\MediaTypes;

/**
 * Represents an HTTP request
 *
 * @api
 * @Flow\Proxy(false)
 */
class Request extends AbstractMessage
{
    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var Uri
     */
    protected $baseUri;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * Data similar to that which is typically provided by $_SERVER
     *
     * @var array
     */
    protected $server;

    /**
     * URI for the "input" stream wrapper which can be modified for testing purposes
     *
     * @var string
     */
    protected $inputStreamUri = 'php://input';

    /**
     * PSR-7
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * PSR-7 Attribute containing the resolved trusted client IP address as string
     */
    const ATTRIBUTE_CLIENT_IP = 'clientIpAddress';

    /**
     * PSR-7 Attribute containing a boolean whether the request is from a trusted proxy
     */
    const ATTRIBUTE_TRUSTED_PROXY = 'fromTrustedProxy';

    /**
     * Constructs a new Request object based on the given environment data.
     *
     * @param array $get Data similar to that which is typically provided by $_GET
     * @param array $post Data similar to that which is typically provided by $_POST
     * @param array $files Data similar to that which is typically provided by $_FILES
     * @param array $server Data similar to that which is typically provided by $_SERVER
     * @see create()
     * @see createFromEnvironment()
     * @api
     */
    public function __construct(array $get, array $post, array $files, array $server)
    {
        $this->headers = Headers::createFromServer($server);
        $this->setAttribute(self::ATTRIBUTE_CLIENT_IP, isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : null);
        $method = isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET';
        if ($method === 'POST') {
            if (isset($post['__method'])) {
                $method = $post['__method'];
            } elseif (isset($server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = $server['HTTP_X_HTTP_METHOD_OVERRIDE'];
            } elseif (isset($server['HTTP_X_HTTP_METHOD'])) {
                $method = $server['HTTP_X_HTTP_METHOD'];
            }
        }
        $this->setMethod($method);

        $protocol = isset($server['SSL_SESSION_ID']) || (isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || strcmp($server['HTTPS'], '1') === 0)) ? 'https' : 'http';
        $host = isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : 'localhost';
        $requestUri = isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/';
        if (substr($requestUri, 0, 10) === '/index.php') {
            $requestUri = '/' . ltrim(substr($requestUri, 10), '/');
        }
        $this->uri = new Uri($protocol . '://' . $host . $requestUri);

        if (isset($server['SERVER_PORT'])) {
            $this->uri->setPort($server['SERVER_PORT']);
        }

        $this->server = $server;
        $this->arguments = $this->buildUnifiedArguments($get, $post, $files);
    }

    /**
     * Creates a new Request object from the given data.
     *
     * @param Uri $uri The request URI
     * @param string $method Request method, for example "GET"
     * @param array $arguments Arguments to send in the request body
     * @param array $files
     * @param array $server
     * @return Request
     * @api
     */
    public static function create(Uri $uri, $method = 'GET', array $arguments = [], array $files = [], array $server = [])
    {
        $get = $uri->getArguments();
        $post = $arguments;

        $isDefaultPort = $uri->getScheme() === 'https' ? ($uri->getPort() === 443) : ($uri->getPort() === 80);

        $defaultServerEnvironment = [
            'HTTP_USER_AGENT' => 'Flow/' . FLOW_VERSION_BRANCH . '.x',
            'HTTP_HOST' => $uri->getHost() . ($isDefaultPort !== true && $uri->getPort() !== null ? ':' . $uri->getPort() : ''),
            'SERVER_NAME' => $uri->getHost(),
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => $uri->getPort() ?: 80,
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_FILENAME' => FLOW_PATH_WEB . 'index.php',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
        ];

        if ($uri->getScheme() === 'https') {
            $defaultServerEnvironment['HTTPS'] = 'on';
            $defaultServerEnvironment['SERVER_PORT'] = $uri->getPort() ?: 443;
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $defaultServerEnvironment['HTTP_CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }

        $query = (string)$uri->getQuery();
        $fragment = $uri->getFragment();
        $overrideValues = [
            'REQUEST_URI' => $uri->getPath() . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . $fragment : ''),
            'REQUEST_METHOD' => $method,
            'QUERY_STRING' => $query
        ];
        $server = array_replace($defaultServerEnvironment, $server, $overrideValues);

        return new static($get, $post, $files, $server);
    }

    /**
     * Considers the environment information found in PHP's superglobals and Flow's
     * environment configuration and creates a new instance of this Request class
     * matching that data.
     *
     * @return Request
     * @api
     */
    public static function createFromEnvironment()
    {
        $request = new static($_GET, $_POST, $_FILES, $_SERVER);
        $request->setContent(null);
        return $request;
    }

    /**
     * Creates a deep clone
     */
    public function __clone()
    {
        $this->uri = clone $this->uri;
        if ($this->baseUri !== null) {
            $this->baseUri = clone $this->baseUri;
        }
        $this->headers = clone $this->headers;
    }

    /**
     * Returns the request URI
     *
     * @return Uri
     * @api
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns the detected base URI
     *
     * @return Uri
     * @api
     */
    public function getBaseUri()
    {
        if ($this->baseUri === null) {
            $this->detectBaseUri();
        }
        return $this->baseUri;
    }

    /**
     * @param Uri $baseUri
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;
    }

    /**
     * Indicates if this request has been received through a secure channel.
     *
     * @return boolean
     * @api
     */
    public function isSecure()
    {
        return $this->uri->getScheme() === 'https';
    }

    /**
     * Sets the request method
     *
     * @param string $method The request method, for example "GET".
     * @return void
     * @api
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Returns the request method
     *
     * @return string The request method
     * @api
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the port used for this request
     *
     * @return integer
     * @api
     */
    public function getPort()
    {
        return $this->uri->getPort();
    }

    /**
     * Tells if the request method is "safe", that is, it is expected to not take any
     * other action than retrieval. This should the case with "GET" and "HEAD" requests.
     *
     * @return boolean
     * @api
     */
    public function isMethodSafe()
    {
        return (in_array($this->method, ['GET', 'HEAD']));
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
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Checks if an argument of the given name exists in this request.
     * Applies to GET, POST and PUT arguments similarly.
     *
     * @param string $name Name of the argument
     * @return boolean
     * @api
     */
    public function hasArgument($name)
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Returns the value of the specified GET / POST / PUT argument.
     *
     * @param string $name Name of the argument
     * @return mixed Value of the specified argument or NULL if it does not exist
     * @api
     */
    public function getArgument($name)
    {
        return (isset($this->arguments[$name]) ? $this->arguments[$name] : null);
    }

    /**
     * Explicitly sets the content of the request body
     *
     * In most cases, content is just a string representation of the request body.
     * In order to reduce memory consumption for uploads and other big data, it is
     * also possible to pass a stream resource. The easies way to convert a local file
     * into a stream resource is probably: $resource = fopen('file://path/to/file', 'rb');
     *
     * @param string|resource $content The body content, for example arguments of a PUT request, or a stream resource
     * @return void
     * @api
     */
    public function setContent($content)
    {
        if (is_resource($content) && get_resource_type($content) === 'stream' && stream_is_local($content)) {
            $streamMetaData = stream_get_meta_data($content);
            $this->headers->set('Content-Length', filesize($streamMetaData['uri']));
            $this->headers->set('Content-Type', MediaTypes::getMediaTypeFromFilename($streamMetaData['uri']));
        }

        parent::setContent($content);
    }

    /**
     * Returns the content of the request body
     *
     * If the request body has not been set with setContent() previously, this method
     * will try to retrieve it from the input stream. If $asResource was set to TRUE,
     * the stream resource will be returned instead of a string.
     *
     * If the content which has been set by setContent() originally was a stream
     * resource, that resource will be returned, no matter if $asResource is set.
     *
     *
     * @param boolean $asResource If set, the content is returned as a resource pointing to PHP's input stream
     * @return string|resource
     * @api
     * @throws Exception
     */
    public function getContent($asResource = false)
    {
        if ($asResource === true) {
            if ($this->content !== null) {
                throw new Exception('Cannot return request content as resource because it has already been retrieved.', 1332942478);
            }
            $this->content = '';
            return fopen($this->inputStreamUri, 'rb');
        }

        if ($this->content === null) {
            $this->content = file_get_contents($this->inputStreamUri);
        }
        return $this->content;
    }

    /**
     * PSR-7
     *
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * PSR-7
     *
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * PSR-7
     *
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        return $default;
    }

    /**
     * PSR-7
     *
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($name, $value)
    {
        $request = clone $this;
        $request->setAttribute($name, $value);
        return $request;
    }

    /**
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     */
    protected function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * PSR-7
     *
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return self
     */
    public function withoutAttribute($name)
    {
        $request = clone $this;
        $request->unsetAttribute($name);
        return $request;
    }

    /**
     * @param string $name The attribute name.
     */
    protected function unsetAttribute($name)
    {
        unset($this->attributes[$name]);
    }

    /**
     * Returns the best guess of the client's IP address.
     *
     * Note that, depending on the actual source used, IP addresses can be spoofed
     * and may not be reliable. Although several kinds of proxy headers are taken into
     * account, certain combinations of ISPs and proxies might still produce wrong
     * results.
     *
     * Don't rely on the client IP address as the only security measure!
     *
     * @return string The client's IP address
     * @api
     */
    public function getClientIpAddress()
    {
        $serverFields = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED');
        foreach ($serverFields as $field) {
            if (empty($this->server[$field])) {
                continue;
            }
            $length = strpos($this->server[$field], ',');
            $ipAddress = trim(($length === false) ? $this->server[$field] : substr($this->server[$field], 0, $length));
            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE) !== false) {
                return $ipAddress;
            }
        }
        return (isset($this->server['REMOTE_ADDR']) ? $this->server['REMOTE_ADDR'] : null);
    }

    /**
     * Returns an list of IANA media types defined in the Accept header.
     *
     * The list is ordered by user preference, after evaluating the Quality Values
     * specified in the header field value. First items in the list are the most
     * preferred.
     *
     * If no Accept header is present, the media type representing "any" media type
     * is returned.
     *
     * @return array A list of media types and sub types
     * @api
     */
    public function getAcceptedMediaTypes()
    {
        $rawValues = $this->headers->get('Accept');
        if (empty($rawValues)) {
            return ['*/*'];
        }
        $acceptedMediaTypes = self::parseContentNegotiationQualityValues($rawValues);
        return $acceptedMediaTypes;
    }

    /**
     * Returns the best fitting IANA media type after applying the content negotiation
     * rules on a possible Accept header.
     *
     * @param array $supportedMediaTypes A list of media types which are supported by the application / controller
     * @param boolean $trim If TRUE, only the type/subtype of the media type is returned. If FALSE, the full original media type string is returned.
     * @return string The media type and sub type which matched, NULL if none matched
     * @api
     */
    public function getNegotiatedMediaType(array $supportedMediaTypes, $trim = true)
    {
        $negotiatedMediaType = null;
        $acceptedMediaTypes = $this->getAcceptedMediaTypes();
        foreach ($acceptedMediaTypes as $acceptedMediaType) {
            foreach ($supportedMediaTypes as $supportedMediaType) {
                if (MediaTypes::mediaRangeMatches($acceptedMediaType, $supportedMediaType)) {
                    $negotiatedMediaType = $supportedMediaType;
                    break 2;
                }
            }
        }
        return ($trim ? MediaTypes::trimMediaType($negotiatedMediaType) : $negotiatedMediaType);
    }

    /**
     * Returns the relative path (ie. relative to the web root) and name of the
     * script as it was accessed through the web server.
     *
     * @return string Relative path and name of the PHP script as accessed through the web
     * @api
     */
    public function getScriptRequestPathAndFilename()
    {
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
     * it was accessed through the web server.
     *
     * @return string Relative path to the PHP script as accessed through the web
     * @api
     */
    public function getScriptRequestPath()
    {
        $requestPathSegments = explode('/', $this->getScriptRequestPathAndFilename());
        array_pop($requestPathSegments);
        return implode('/', $requestPathSegments) . '/';
    }

    /**
     * Returns the request's path relative to the $baseUri
     *
     * @return string
     */
    public function getRelativePath()
    {
        $baseUriLength = strlen($this->getBaseUri()->getPath());
        if ($baseUriLength >= strlen($this->getUri()->getPath())) {
            return '';
        }
        return substr($this->getUri()->getPath(), $baseUriLength);
    }

    /**
     * Return the Request-Line of this Request Message, consisting of the method, the uri and the HTTP version
     * Would be, for example, "GET /foo?bar=baz HTTP/1.1"
     * Note that the URI part is, at the moment, only possible in the form "abs_path" since the
     * actual requestUri of the Request cannot be determined during the creation of the Request.
     *
     * @return string
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1
     * @api
     */
    public function getRequestLine()
    {
        $requestUri = $this->uri->getPath() .
            ($this->uri->getQuery() ? '?' . $this->uri->getQuery() : '') .
            ($this->uri->getFragment() ? '#' . $this->uri->getFragment() : '');
        return sprintf("%s %s %s\r\n", $this->method, $requestUri, $this->version);
    }

    /**
     * Returns the first line of this Request Message, which is the Request-Line in this case
     *
     * @return string The Request-Line of this Request
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html chapter 4.1 "Message Types"
     * @api
     */
    public function getStartLine()
    {
        return $this->getRequestLine();
    }

    /**
     * Tries to detect the base URI of request.
     *
     * @return void
     */
    protected function detectBaseUri()
    {
        if ($this->baseUri === null) {
            $this->baseUri = clone $this->uri;
            $this->baseUri->setQuery(null);
            $this->baseUri->setFragment(null);
            $this->baseUri->setPath($this->getScriptRequestPath());
        }
    }

    /**
     * Takes the raw GET & POST arguments and maps them into the request object.
     * Afterwards all mapped arguments can be retrieved by the getArgument(s) method, no matter if they
     * have been GET, POST or PUT arguments before.
     *
     * @param array $getArguments Arguments as found in $_GET
     * @param array $postArguments Arguments as found in $_POST
     * @param array $uploadArguments Arguments as found in $_FILES
     * @return array the unified arguments
     */
    protected function buildUnifiedArguments(array $getArguments, array $postArguments, array $uploadArguments)
    {
        $arguments = $getArguments;
        $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $postArguments);
        $arguments = Arrays::arrayMergeRecursiveOverrule($arguments, $this->untangleFilesArray($uploadArguments));
        return $arguments;
    }

    /**
     * Transforms the convoluted _FILES superglobal into a manageable form.
     *
     * @param array $convolutedFiles The _FILES superglobal
     * @return array Untangled files
     */
    protected function untangleFilesArray(array $convolutedFiles)
    {
        $untangledFiles = [];

        $fieldPaths = [];
        foreach ($convolutedFiles as $firstLevelFieldName => $fieldInformation) {
            if (!is_array($fieldInformation['error'])) {
                $fieldPaths[] = [$firstLevelFieldName];
            } else {
                $newFieldPaths = $this->calculateFieldPaths($fieldInformation['error'], $firstLevelFieldName);
                array_walk($newFieldPaths,
                    function (&$value) {
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
                $fileInformation = [];
                foreach ($convolutedFiles[$fieldPath{0}] as $key => $subStructure) {
                    $fileInformation[$key] = Arrays::getValueByPath($subStructure, array_slice($fieldPath, 1));
                }
            }
            if (isset($fileInformation['error']) && $fileInformation['error'] !== \UPLOAD_ERR_NO_FILE) {
                $untangledFiles = Arrays::setValueByPath($untangledFiles, $fieldPath, $fileInformation);
            }
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
    protected function calculateFieldPaths(array $structure, $firstLevelFieldName = null)
    {
        $fieldPaths = [];
        if (is_array($structure)) {
            foreach ($structure as $key => $subStructure) {
                $fieldPath = ($firstLevelFieldName !== null ? $firstLevelFieldName . '/' : '') . $key;
                if (is_array($subStructure)) {
                    foreach ($this->calculateFieldPaths($subStructure) as $subFieldPath) {
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
     * Parses a RFC 2616 content negotiation header field by evaluating the Quality
     * Values and splitting the options into an array list, ordered by user preference.
     *
     * @param string $rawValues The raw Accept* Header field value
     * @return array The parsed list of field values, ordered by user preference
     */
    public static function parseContentNegotiationQualityValues($rawValues)
    {
        $acceptedTypes = array_map(
            function ($acceptType) {
                $typeAndQuality = preg_split('/;\s*q=/', $acceptType);
                return [$typeAndQuality[0], (isset($typeAndQuality[1]) ? (float)$typeAndQuality[1] : '')];
            }, preg_split('/,\s*/', $rawValues)
        );

        $flattenedAcceptedTypes = [];
        $valuesWithoutQualityValue = [[], [], [], []];
        foreach ($acceptedTypes as $typeAndQuality) {
            if ($typeAndQuality[1] === '') {
                $parsedType = MediaTypes::parseMediaType($typeAndQuality[0]);
                if ($parsedType['type'] === '*') {
                    $valuesWithoutQualityValue[3][$typeAndQuality[0]] = true;
                } elseif ($parsedType['subtype'] === '*') {
                    $valuesWithoutQualityValue[2][$typeAndQuality[0]] = true;
                } elseif ($parsedType['parameters'] === []) {
                    $valuesWithoutQualityValue[1][$typeAndQuality[0]] = true;
                } else {
                    $valuesWithoutQualityValue[0][$typeAndQuality[0]] = true;
                }
            } else {
                $flattenedAcceptedTypes[$typeAndQuality[0]] = $typeAndQuality[1];
            }
        }
        $valuesWithoutQualityValue = array_merge(array_keys($valuesWithoutQualityValue[0]), array_keys($valuesWithoutQualityValue[1]), array_keys($valuesWithoutQualityValue[2]), array_keys($valuesWithoutQualityValue[3]));
        arsort($flattenedAcceptedTypes);
        $parsedValues = array_merge($valuesWithoutQualityValue, array_keys($flattenedAcceptedTypes));
        return $parsedValues;
    }

    /**
     * Renders the HTTP headers - including the status header - of this request
     *
     * @return string The HTTP headers, one per line, separated by \r\n as required by RFC 2616 sec 5
     * @api
     */
    public function renderHeaders()
    {
        $headers = $this->getRequestLine();

        foreach ($this->headers->getAll() as $name => $values) {
            foreach ($values as $value) {
                $headers .= sprintf("%s: %s\r\n", $name, $value);
            }
        }

        return $headers;
    }

    /**
     * Cast the request to a string: return the content part of this response
     *
     * @return string The same as getContent()
     * @api
     */
    public function __toString()
    {
        return $this->renderHeaders() . "\r\n" . $this->getContent();
    }
}
