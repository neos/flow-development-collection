<?php
namespace TYPO3\Flow\Http;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * Represents a HTTP message
 *
 * @api
 * @Flow\Proxy(false)
 */
abstract class AbstractMessage implements MessageInterface
{
    /**
     * The HTTP version value of this message, for example "HTTP/1.1"
     *
     * @var string
     */
    protected $version = 'HTTP/1.1';

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * Entity body of this message
     *
     * @var string
     */
    protected $content = '';

    /**
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     *
     */
    public function __construct()
    {
        $this->headers = new Headers();
    }

    /**
     * Returns the HTTP headers of this request
     *
     * @return Headers
     * @api
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns the value(s) of the specified header
     *
     * If one such header exists, the value is returned as a single string.
     * If multiple headers of that name exist, the values are returned as an array.
     * If no such header exists, NULL is returned.
     *
     * Dates are returned as DateTime objects with the timezone set to GMT.
     *
     * @param string $name Name of the header
     * @return array|string An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
     * @api
     */
    public function getHeader($name)
    {
        return $this->headers->get($name);
    }

    /**
     * Checks if the specified header exists.
     *
     * @param string $name Name of the HTTP header
     * @return boolean
     * @api
     */
    public function hasHeader($name)
    {
        return $this->headers->has($name);
    }

    /**
     * Sets the specified HTTP header
     *
     * DateTime objects will be converted to a string representation internally but
     * will be returned as DateTime objects on calling getHeader().
     *
     * Please note that dates are normalized to GMT internally, so that getHeader() will return
     * the same point in time, but not necessarily in the same timezone, if it was not
     * GMT previously. GMT is used synonymously with UTC as per RFC 2616 3.3.1.
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @param array|string|\DateTime $values An array of values or a single value for the specified header field
     * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
     * @return self This message, for method chaining
     * @throws \InvalidArgumentException
     * @api
     */
    public function setHeader($name, $values, $replaceExistingHeader = true)
    {
        switch ($name) {
            case 'Content-Type':
                if (is_array($values)) {
                    if (count($values) !== 1) {
                        throw new \InvalidArgumentException('The "Content-Type" header must be unique and thus only one field value may be specified.', 1454949291);
                    }
                    $values = (string) $values[0];
                }
                if (stripos($values, 'charset') === false && stripos($values, 'text/') === 0) {
                    $values .= '; charset=' . $this->charset;
                }
            break;
        }

        $this->headers->set($name, $values, $replaceExistingHeader);
        return $this;
    }

    /**
     * Explicitly sets the content of the message body
     *
     * @param string $content The body content
     * @return self This message, for method chaining
     * @api
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Returns the content of the message body
     *
     * @return string The response content
     * @api
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the character set for this message.
     *
     * If the content type of this message is a text/* media type, the character
     * set in the respective Content-Type header will be updated by this method.
     *
     * @param string $charset A valid IANA character set identifier
     * @return self This message, for method chaining
     * @see http://www.iana.org/assignments/character-sets
     * @api
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        if ($this->headers->has('Content-Type')) {
            $contentType = $this->headers->get('Content-Type');
            if (stripos($contentType, 'text/') === 0) {
                $matches = [];
                if (preg_match('/(?P<contenttype>.*); ?charset[^;]+(?P<extra>;.*)?/iu', $contentType, $matches)) {
                    $contentType = $matches['contenttype'];
                }
                $contentType .= '; charset=' . $this->charset . (isset($matches['extra']) ? $matches['extra'] : '');
                $this->setHeader('Content-Type', $contentType, true);
            }
        }
        return $this;
    }

    /**
     * Returns the character set of this response.
     *
     * Note that the default character in Flow is UTF-8.
     *
     * @return string An IANA character set identifier
     * @api
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Sets the HTTP version value of this message, for example "HTTP/1.1"
     *
     * @param string $version
     * @return void
     * @api
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Returns the HTTP version value of this message, for example "HTTP/1.1"
     *
     * @return string
     * @api
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the given cookie to in the headers of this message.
     *
     * This is a shortcut for $message->getHeaders()->setCookie($cookie);
     *
     * @param Cookie $cookie The cookie to set
     * @return void
     * @api
     */
    public function setCookie(Cookie $cookie)
    {
        $this->headers->setCookie($cookie);
    }

    /**
     * Returns a cookie specified by the given name
     *
     * This is a shortcut for $message->getHeaders()->getCookie($name);
     *
     * @param string $name Name of the cookie
     * @return Cookie The cookie or NULL if no such cookie exists
     * @api
     */
    public function getCookie($name)
    {
        return $this->headers->getCookie($name);
    }

    /**
     * Returns all cookies attached to the headers of this message
     *
     * This is a shortcut for $message->getHeaders()->getCookies();
     *
     * @return array An array of Cookie objects
     * @api
     */
    public function getCookies()
    {
        return $this->headers->getCookies();
    }

    /**
     * Checks if the specified cookie exists
     *
     * This is a shortcut for $message->getHeaders()->hasCookie($name);
     *
     * @param string $name Name of the cookie
     * @return boolean
     * @api
     */
    public function hasCookie($name)
    {
        return $this->headers->hasCookie($name);
    }

    /**
     * Removes the specified cookie from the headers of this message, if it exists
     *
     * This is a shortcut for $message->getHeaders()->removeCookie($name);
     *
     * Note: This will remove the cookie object from this Headers container. If you
     *       intend to remove a cookie in the user agent (browser), you should call
     *       the cookie's expire() method and _not_ remove the cookie from the Headers
     *       container.
     *
     * @param string $name Name of the cookie to remove
     * @return void
     * @api
     */
    public function removeCookie($name)
    {
        $this->headers->removeCookie($name);
    }

    /**
     * Returns the first line of this message, which is the Request's Request-Line or the Response's Status-Line.
     *
     * @return string The first line of the message
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html chapter 4.1 "Message Types"
     * @api
     */
    abstract public function getStartLine();

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * PSR-7 MessageInterface
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return explode('/', $this->version)[1];
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * PSR-7 MessageInterface
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $newMessage = clone $this;
        $newMessage->setVersion('HTTP/' . $version);

        return $newMessage;
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name)
    {
        $headerLine = $this->headers->get($name);
        if ($headerLine === null) {
            $headerLine = '';
        }

        if (is_array($headerLine)) {
            $headerLine = explode(', ', $headerLine);
        }

        return $headerLine;
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $newMessage = clone $this;
        $newMessage->setHeader($name, $value, true);
        return $newMessage;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $newMessage = clone $this;
        $newMessage->setHeader($name, $value);

        return $newMessage;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return self
     */
    public function withoutHeader($name)
    {
        $newMessage = clone $this;
        $newMessage->getHeaders()->remove($name);

        return $newMessage;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody()
    {
        $streamResource = fopen('php://temp', 'w+');
        fwrite($streamResource, $this->content);

        return new ContentStream($streamResource);
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return self
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $newMessage = clone $this;
        $newMessage->setContent($body->getContents());

        return $newMessage;
    }

    /**
     * Headers should also be cloned when the message is cloned.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
}
