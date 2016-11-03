<?php
namespace TYPO3\Flow\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\Flow\Utility\MediaTypes;

/**
 *
 */
class BaseRequest extends AbstractMessage implements RequestInterface
{
    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var Uri
     */
    protected $baseUri;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * URI for the "input" stream wrapper which can be modified for testing purposes
     *
     * @var string
     */
    protected $inputStreamUri = null;

    /**
     * BaseRequest constructor.
     *
     * @param UriInterface $uri
     * @param string $method
     */
    public function __construct(UriInterface $uri, $method = 'GET')
    {
        $this->uri = $uri;
        $this->method = $method;
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

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->uri->getPath();
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return self
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $newRequest = clone $this;
        $newRequest->setMethod($method);

        return $newRequest;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $newRequest = clone $this;
        $newRequest->uri = $uri;

        $host = $uri->getHost();
        if ($preserveHost === false) {
            if ($host !== '') {
                $newRequest->setHeader('Host', $host);
            }
        } else {
            if (($newRequest->hasHeader('Host') === false || trim($newRequest->getHeader('Host')) === '')  && $host !== '') {
                $newRequest->setHeader('Host', $host);
            }
        }

        return $newRequest;
    }

    /**
     * When this Request is cloned also Headers, Uri and BaseUri must be cloned.
     */
    public function __clone()
    {
        parent::__clone();
        $this->headers = clone $this->headers;
        $this->uri = clone $this->uri;
        $this->baseUri = clone $this->baseUri;
    }
}
