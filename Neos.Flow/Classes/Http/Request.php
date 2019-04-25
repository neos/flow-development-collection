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
use Neos\Flow\Http\Helper\ArgumentsHelper;
use Neos\Flow\Http\Helper\MediaTypeHelper;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\Helper\SecurityHelper;
use Neos\Flow\Http\Helper\UploadedFilesHelper;
use Neos\Flow\Http\Helper\UriHelper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents an HTTP request in the PHP world, inlcuding server variables.
 * This is the object used for incoming requests from a browser.
 *
 * TODO: Maybe separate BaseRequest and Request implementations (trait?)
 *
 * @api
 * @Flow\Proxy(false)
 */
class Request extends BaseRequest implements ServerRequestInterface
{
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
     * PSR-7
     *
     * @var array
     */
    protected $queryParams = [];

    /**
     * PSR-7
     *
     * @var array|object|null
     */
    protected $parsedBody;

    /**
     * PSR-7
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * PSR-7 Attribute containing the resolved trusted client IP address as string
     */
    const ATTRIBUTE_CLIENT_IP = 'clientIpAddress';

    /**
     * PSR-7 Attribute containing a boolean whether the request is from a trusted proxy
     */
    const ATTRIBUTE_TRUSTED_PROXY = 'fromTrustedProxy';

    /**
     * PSR-7 Attribute containing the base URI for this request.
     */
    const ATTRIBUTE_BASE_URI = 'baseUri';

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
        $this->method = $method;

        $protocol = isset($server['SSL_SESSION_ID']) || (isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || strcmp($server['HTTPS'], '1') === 0)) ? 'https' : 'http';
        $host = isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : 'localhost';
        $requestUri = isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/';
        if (substr($requestUri, 0, 10) === '/index.php') {
            $requestUri = '/' . ltrim(substr($requestUri, 10), '/');
        }

        $uri = new Uri($protocol . '://' . $host . $requestUri);
        if (isset($server['SERVER_PORT'])) {
            $uri = $uri->withPort($server['SERVER_PORT']);
        }

        $this->uri = $uri;
        $this->parsedBody = $post;
        $this->queryParams = $get;
        $this->server = $server;
        $untangledFiles = UploadedFilesHelper::untangleFilesArray($files);
        $this->arguments = ArgumentsHelper::buildUnifiedArguments($get, $post, $untangledFiles);
        $this->uploadedFiles = $this->createUploadedFilesFromUntangledUploads($untangledFiles, $this->arguments);
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
        $get = UriHelper::parseQueryIntoArguments($uri);
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
            'REQUEST_TIME' => time()
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
     * Indicates if this request has been received through a secure channel.
     *
     * @return boolean
     * @deprecated Since Flow 5.1, use the SecurityHelper
     * @see SecurityHelper::isSecureRequest()
     */
    public function isSecure()
    {
        return SecurityHelper::isSecureRequest($this);
    }

    /**
     * Returns the port used for this request
     *
     * @return integer
     * @deprecated Since Flow 5.1, use $this->getUri()->getPort() instead.
     * @see getUri()
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
     * @deprecated Since Flow 5.1, use the SecurityHelper
     * @see SecurityHelper::hasSafeMethod()
     */
    public function isMethodSafe()
    {
        return SecurityHelper::hasSafeMethod($this);
    }

    /**
     * Returns the detected base URI
     *
     * @return UriInterface|Uri
     * @deprecated Since Flow 5.1, use attribute
     * @see Request::getAttribute(Request::ATTRIBUTE_BASE_URI)
     */
    public function getBaseUri()
    {
        if (!isset($this->attributes[self::ATTRIBUTE_BASE_URI])) {
            $this->attributes[self::ATTRIBUTE_BASE_URI] = $this->detectBaseUri();
        }

        return $this->attributes[self::ATTRIBUTE_BASE_URI];
    }

    /**
     * Set the base URI of this request.
     *
     * @param string|UriInterface $baseUri
     * @deprecated Since Flow 5.1, set as an attribute on ServerRequestInterface instead.
     * @see Request::withAttribute(Request::ATTRIBUTE_BASE_URI)
     */
    public function setBaseUri($baseUri)
    {
        if (is_string($baseUri)) {
            $baseUri = new Uri($baseUri);
        }

        $this->attributes[self::ATTRIBUTE_BASE_URI] = $baseUri;
    }

    /**
     * Tries to detect the base URI of request.
     *
     * @return UriInterface
     * @deprecated Since Flow 5.1
     */
    protected function detectBaseUri()
    {
        $baseUri = clone $this->uri;
        $baseUri = $baseUri->withQuery('')->withFragment('')->withPath(RequestInformationHelper::getScriptRequestPath($this));

        return $baseUri;
    }

    /**
     * Returns the unified arguments of this request.
     *
     * GET, POST and PUT arguments, as well es uploaded files, are merged into a whole
     * array of arguments.
     *
     * @return array
     * @deprecated Since Flow 5.1, use ArgumentsHelper to get an array of unified arguments
     * @see ArgumentsHelper::buildUnifiedArguments()
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
     * @deprecated Since Flow 5.1, use ArgumentsHelper to get an array of unified arguments
     * @see ArgumentsHelper::buildUnifiedArguments()
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
     * @deprecated Since Flow 5.1, use ArgumentsHelper to get an array of unified arguments
     * @see ArgumentsHelper::buildUnifiedArguments()
     */
    public function getArgument($name)
    {
        return (isset($this->arguments[$name]) ? $this->arguments[$name] : null);
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
     * @deprecated Since Flow 5.1, use ->getAttribute(Request::ATTRIBUTE_CLIENT_IP)
     */
    public function getClientIpAddress()
    {
        return $this->getAttribute(self::ATTRIBUTE_CLIENT_IP);
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
     *
     * @deprecated Since Flow 5.1, in favor of using the separate MediaTypeHelper
     * @see MediaTypeHelper::determineAcceptedMediaTypes()
     */
    public function getAcceptedMediaTypes()
    {
        return MediaTypeHelper::determineAcceptedMediaTypes($this);
    }

    /**
     * Returns the best fitting IANA media type after applying the content negotiation
     * rules on a possible Accept header.
     *
     * @param array $supportedMediaTypes A list of media types which are supported by the application / controller
     * @param boolean $trim If true, only the type/subtype of the media type is returned. If false, the full original media type string is returned.
     * @return string The media type and sub type which matched, NULL if none matched
     *
     * @deprecated Since Flow 5.1, in favor of using the separate MediaTypeHelper
     * @see MediaTypeHelper::negotiateMediaType()
     */
    public function getNegotiatedMediaType(array $supportedMediaTypes, $trim = true)
    {
        return MediaTypeHelper::negotiateMediaType(MediaTypeHelper::determineAcceptedMediaTypes($this), $supportedMediaTypes, $trim);
    }

    /**
     * Returns the relative path (i.e. relative to the web root) and name of the
     * script as it was accessed through the web server.
     *
     * @return string Relative path and name of the PHP script as accessed through the web
     * @deprecated Since Flow 5.1, use the RequestInformationHelper instead
     * @see RequestInformationHelper::getScriptRequestPathAndFilename()
     */
    public function getScriptRequestPathAndFilename()
    {
        return RequestInformationHelper::getScriptRequestPathAndFilename($this);
    }

    /**
     * Returns the relative path (i.e. relative to the web root) to the script as
     * it was accessed through the web server.
     *
     * @return string Relative path to the PHP script as accessed through the web
     * @deprecated Since Flow 5.1, use the RequestInformationHelper instead
     * @see RequestInformationHelper::getScriptRequestPath()
     */
    public function getScriptRequestPath()
    {
        return RequestInformationHelper::getScriptRequestPath($this);
    }

    /**
     * Returns the request's path relative to the $baseUri
     *
     * @return string
     * @deprecated Since Flow 5.1, use the RequestInformationHelper instead
     * @see UriHelper::getRelativePath()
     */
    public function getRelativePath()
    {
        return UriHelper::getRelativePath($this->getBaseUri(), $this->getUri());
    }

    /**
     * @param array $untangledFilesStructure
     * @param array $arguments
     * @return array
     */
    protected function createUploadedFilesFromUntangledUploads(array $untangledFilesStructure, array $arguments)
    {
        $uploadedFiles = [];
        foreach ($untangledFilesStructure as $key => $nestedStructure) {
            if (!isset($nestedStructure['tmp_name'])) {
                $uploadedFiles[$key] = $this->createUploadedFilesFromUntangledUploads($nestedStructure, $arguments[$key]);
                continue;
            }
            $uploadedFiles[$key] = $this->createUploadedFileFromSpec($nestedStructure, $arguments[$key]);
        }

        return $uploadedFiles;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @param array $argumentsForValue
     * @return UploadedFileInterface
     */
    protected function createUploadedFileFromSpec(array $value, $argumentsForValue)
    {
        $file = new FlowUploadedFile(
            $value['tmp_name'],
            (int)$value['size'],
            (int)$value['error'],
            $value['name'],
            $value['type']
        );

        if (!empty($argumentsForValue['originallySubmittedResource'])) {
            $file->setOriginallySubmittedResource($argumentsForValue['originallySubmittedResource']);
        }

        if (!empty($argumentsForValue['__collectionName'])) {
            $file->setCollectionName($argumentsForValue['__collectionName']);
        }

        return $file;
    }

    /**
     * Parses a RFC 2616 content negotiation header field by evaluating the Quality
     * Values and splitting the options into an array list, ordered by user preference.
     *
     * @param string $rawValues The raw Accept* Header field value
     * @return array The parsed list of field values, ordered by user preference
     *
     * @deprecated Since Flow 5.1, in favor of using the separate MediaTypeHelper
     * @see MediaTypeHelper::parseContentNegotiationQualityValues()
     */
    public static function parseContentNegotiationQualityValues($rawValues)
    {
        return MediaTypeHelper::parseContentNegotiationQualityValues($rawValues);
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        $cookies = [];
        foreach ($this->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        };
        return $cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $newRequest = clone $this;
        foreach ($cookies as $name => $value) {
            $newRequest->headers->setCookie(new Cookie($name, $value));
        }
        return $newRequest;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from $_GET.
     * @return static
     * @api PSR-7
     */
    public function withQueryParams(array $query)
    {
        $newRequest = clone $this;
        $newRequest->queryParams = $query;
        return $newRequest;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     * @api PSR-7
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles
     * @return ServerRequestInterface
     * @api PSR-7
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $newRequest = clone $this;
        $newRequest->uploadedFiles = $uploadedFiles;
        return $newRequest;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return array|null|object
     * @api PSR-7
     */
    public function getParsedBody()
    {
        return $this->parsedBody ?? $this->arguments;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array|null|object $data
     * @return ServerRequestInterface
     * @api PSR-7
     */
    public function withParsedBody($data)
    {
        $newRequest = clone $this;
        $newRequest->parsedBody = $data;
        return $newRequest;
    }
}
