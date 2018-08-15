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

use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Mvc\ResponseInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Represents an HTTP Response
 *
 * @api
 * @Flow\Proxy(false)
 */
class Response extends AbstractMessage implements ResponseInterface, \Psr\Http\Message\ResponseInterface
{
    /**
     * @var Response
     */
    protected $parentResponse;

    /**
     * The HTTP status code
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * The HTTP status message
     * @var string
     */
    protected $statusMessage = 'OK';

    /**
     * The current point in time, used for comparisons
     * @var \DateTime
     */
    protected $now;

    /**
     * Returns the human-readable message for the given status code.
     *
     * @param integer $statusCode
     * @return string
     * @deprecated Since Flow 5.1, use ResponseInformationHelper::getStatusMessageByCode
     * @see ResponseInformationHelper::getStatusMessageByCode()
     */
    public static function getStatusMessageByCode($statusCode)
    {
        ResponseInformationHelper::getStatusMessageByCode($statusCode);
    }

    /**
     * Construct this Response
     *
     * @param ResponseInterface $parentResponse Deprecated parameter
     */
    public function __construct(ResponseInterface $parentResponse = null)
    {
        $this->headers = new Headers();
        $this->headers->set('Content-Type', 'text/html; charset=' . $this->charset);
        $this->parentResponse = $parentResponse;
    }

    /**
     * Creates a response from the given raw, that is plain text, HTTP response.
     *
     * @param string $rawResponse
     * @param Response $parentResponse Deprecated parameter. Parent response, if called recursively
     *
     * @throws \InvalidArgumentException
     * @return \Psr\Http\Message\ResponseInterface
     * @deprecated Since Flow 5.1, use ResponseInformationHelper::createFromRaw
     * @see ResponseInformationHelper::createFromRaw()
     */
    public static function createFromRaw($rawResponse, Response $parentResponse = null)
    {
        /** @var Response $response */
        $response = ResponseInformationHelper::createFromRaw($rawResponse);
        $response->parentResponse = $parentResponse;
        return $response;
    }

    /**
     * Return the parent response or NULL if none exists.
     *
     * @return Response the parent response, or NULL if none
     * @deprecated Since Flow 5.1, without replacement
     */
    public function getParentResponse()
    {
        return $this->parentResponse;
    }

    /**
     * Appends content to the already existing content.
     *
     * @param string $content More response content
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, without replacement
     */
    public function appendContent($content)
    {
        $this->content .= $content;
        return $this;
    }

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @deprecated Since Flow 5.1, use getBody
     * @see getBody()
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the HTTP status code and (optionally) a customized message.
     *
     * @param integer $code The status code
     * @param string $message If specified, this message is sent instead of the standard message
     * @return Response This response, for method chaining
     * @throws \InvalidArgumentException if the specified status code is not valid
     * @deprecated Since Flow 5.1, use withStatus
     * @see withStatus()
     */
    public function setStatus($code, $message = null)
    {
        if (!is_int($code)) {
            throw new \InvalidArgumentException('The HTTP status code must be of type integer, ' . gettype($code) . ' given.', 1220526013);
        }
        if ($message === null) {
            $message = self::getStatusMessageByCode($code);
        }
        $this->statusCode = $code;
        $this->statusMessage = ($message === null) ? ResponseInformationHelper::getStatusMessageByCode($code) : $message;
        return $this;
    }

    /**
     * Returns status code and status message.
     *
     * @return string The status code and status message, eg. "404 Not Found"
     * @deprecated Since Flow 5.1, use getStatusCode
     * @see getStatusCode()
     */
    public function getStatus()
    {
        return $this->statusCode . ' ' . $this->statusMessage;
    }

    /**
     * Returns the status code.
     *
     * @return integer The status code, eg. 404
     * @api
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Replaces all possibly existing HTTP headers with the ones specified
     *
     * @param Headers
     * @return void
     * @deprecated Since Flow 5.1, without replacement
     */
    public function setHeaders(Headers $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Sets the current point in time.
     *
     * This date / time is used internally for comparisons in order to determine the
     * freshness of this response. By default this DateTime object is set automatically
     * through dependency injection, configured in the Objects.yaml of the Flow package.
     *
     * Unless you are mocking the current time in a test, there is probably no need
     * to use this function. Also note that this method must be called before any
     * of the Response methods are used and it must not be called a second time.
     *
     * @param \DateTime $now The current point in time
     * @return void
     * @deprecated Since Flow 5.1, directly set the "Date" header
     * @see withHeader()
     */
    public function setNow(\DateTime $now)
    {
        $this->now = clone $now;
        $this->now->setTimezone(new \DateTimeZone('UTC'));
        $this->headers->set('Date', $this->now);
    }

    /**
     * Sets the Date header.
     *
     * The given date must either be an RFC2822 parseable date string or a DateTime
     * object. The timezone will be converted to GMT internally, but the point in
     * time remains the same.
     *
     * @param string|\DateTime $date
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the "Date" header
     * @see withHeader()
     */
    public function setDate($date)
    {
        $this->headers->set('Date', $date);
        return $this;
    }

    /**
     * Returns the date from the Date header.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime The date of this response
     * @deprecated Since Flow 5.1, directly get the "Date" header
     * @see getHeader()
     */
    public function getDate()
    {
        return $this->headers->get('Date');
    }

    /**
     * Sets the Last-Modified header.
     *
     * The given date must either be an RFC2822 parseable date string or a DateTime
     * object. The timezone will be converted to GMT internally, but the point in
     * time remains the same.
     *
     * @param string|\DateTime $date
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the Last-Modified header
     * @see withHeader()
     */
    public function setLastModified($date)
    {
        $this->headers->set('Last-Modified', $date);
        return $this;
    }

    /**
     * Returns the date from the Last-Modified header or NULL if no such header
     * is present.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime The last modification date or NULL
     * @deprecated Since Flow 5.1, directly get the Last-Modified header
     * @see getHeader()
     */
    public function getLastModified()
    {
        return $this->headers->get('Last-Modified');
    }

    /**
     * Sets the Expires header.
     *
     * The given date must either be an RFC2822 parseable date string or a DateTime
     * object. The timezone will be converted to GMT internally, but the point in
     * time remains the same.
     *
     * In order to signal that the response has already expired, the date should
     * be set to the same date as the Date header (that is, $now). To communicate
     * an infinite expiration time, the date should be set to one year in the future.
     *
     * Expiration times should not be more than one year in the future, according
     * to RFC 2616 / 14.21
     *
     * @param string|\DateTime $date
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the Expires header
     * @see withHeader()
     */
    public function setExpires($date)
    {
        $this->headers->set('Expires', $date);
        return $this;
    }

    /**
     * Returns the date from the Expires header or NULL if no such header
     * is present.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime The expiration date or NULL
     * @deprecated Since Flow 5.1, directly get the Expires header
     * @see getHeader()
     */
    public function getExpires()
    {
        return $this->headers->get('Expires');
    }

    /**
     * Returns the age of this responds in seconds.
     *
     * The age is determined either by an explicitly set Age header or by the
     * difference between Date and "now".
     *
     * Note that, according to RFC 2616 / 13.2.3, the presence of an Age header implies
     * that the response is not first-hand. You should therefore only explicitly set
     * an Age header if this is the case.
     *
     * @return integer The age in seconds
     * @deprecated Since Flow 5.1, directly get the Age header
     * @see getHeader()
     */
    public function getAge()
    {
        if ($this->headers->has('Age')) {
            return $this->headers->get('Age');
        } else {
            $dateTimestamp = $this->headers->get('Date')->getTimestamp();
            $nowTimestamp = $this->now->getTimestamp();
            return ($nowTimestamp > $dateTimestamp) ? ($nowTimestamp - $dateTimestamp) : 0;
        }
    }

    /**
     * Sets the maximum age in seconds before this response becomes stale.
     *
     * This method sets the "max-age" directive in the Cache-Control header.
     *
     * @param integer $age The maximum age in seconds
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the cache header
     * @see withHeader()
     */
    public function setMaximumAge($age)
    {
        $this->headers->setCacheControlDirective('max-age', $age);
        return $this;
    }

    /**
     * Returns the maximum age in seconds before this response becomes stale.
     *
     * This method returns the value from the "max-age" directive in the
     * Cache-Control header.
     *
     * @return integer The maximum age in seconds, or NULL if none has been defined
     * @deprecated Since Flow 5.1, directly get the cache header and parse it
     * @see getHeader()
     */
    public function getMaximumAge()
    {
        return $this->headers->getCacheControlDirective('max-age');
    }

    /**
     * Sets the maximum age in seconds before this response becomes stale in shared
     * caches, such as proxies.
     *
     * This method sets the "s-maxage" directive in the Cache-Control header.
     *
     * @param integer $maximumAge The maximum age in seconds
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the cache header
     * @see withHeader()
     */
    public function setSharedMaximumAge($maximumAge)
    {
        $this->headers->setCacheControlDirective('s-maxage', $maximumAge);
        return $this;
    }

    /**
     * Returns the maximum age in seconds before this response becomes stale in shared
     * caches, such as proxies.
     *
     * This method returns the value from the "s-maxage" directive in the
     * Cache-Control header.
     *
     * @return integer The maximum age in seconds, or NULL if none has been defined
     * @deprecated Since Flow 5.1, directly get the cache header
     * @see getHeader()
     */
    public function getSharedMaximumAge()
    {
        return $this->headers->getCacheControlDirective('s-maxage');
    }

    /**
     * Renders the HTTP headers - including the status header - of this response
     *
     * @return array The HTTP headers
     * @deprecated Since Flow 5.1, use ResponseInformationHelper::prepareHeaders
     * @see ResponseInformationHelper::prepareHeaders()
     */
    public function renderHeaders()
    {
        return ResponseInformationHelper::prepareHeaders($this);
    }

    /**
     * Sets the respective directive in the Cache-Control header.
     *
     * A response flagged as "public" may be cached by any cache, even if it normally
     * wouldn't be cacheable in a shared cache.
     *
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the cache header
     * @see withHeader()
     */
    public function setPublic()
    {
        $this->headers->setCacheControlDirective('public');
        return $this;
    }

    /**
     * Sets the respective directive in the Cache-Control header.
     *
     * A response flagged as "private" tells that it is intended for a specific
     * user and must not be cached by a shared cache.
     *
     * @return Response This response, for method chaining
     * @deprecated Since Flow 5.1, directly set the cache header
     * @see withHeader()
     */
    public function setPrivate()
    {
        $this->headers->setCacheControlDirective('private');
        return $this;
    }

    /**
     * Analyzes this response, considering the given request and makes additions
     * or removes certain headers in order to make the response compliant to
     * RFC 2616 and related standards.
     *
     * It is recommended to call this method before the response is sent and Flow
     * does so by default in its built-in HTTP request handler.
     *
     * @param Request $request The corresponding request
     * @return void
     * @deprecated Since Flow 5.1, use ResponseInformationHelper::makeStandardsCompliant
     * @see ResponseInformationHelper::makeStandardsCompliant()
     */
    public function makeStandardsCompliant(Request $request)
    {
        if ($request->hasHeader('If-Modified-Since') && $this->headers->has('Last-Modified') && $this->statusCode === 200) {
            $ifModifiedSinceDate = $request->getHeader('If-Modified-Since');
            $lastModifiedDate = $this->headers->get('Last-Modified');
            if ($lastModifiedDate <= $ifModifiedSinceDate) {
                $this->setStatus(304);
                $this->content = '';
            }
        } elseif ($request->hasHeader('If-Unmodified-Since') && $this->headers->has('Last-Modified')
                && (($this->statusCode >= 200 && $this->statusCode <= 299) || $this->statusCode === 412)) {
            $unmodifiedSinceDate = $request->getHeader('If-Unmodified-Since');
            $lastModifiedDate = $this->headers->get('Last-Modified');
            if ($lastModifiedDate > $unmodifiedSinceDate) {
                $this->setStatus(412);
            }
        }

        if (in_array($this->statusCode, [100, 101, 204, 304])) {
            $this->content = '';
        }

        if ($this->headers->getCacheControlDirective('no-cache') !== null
                || $this->headers->has('Expires')) {
            $this->headers->removeCacheControlDirective('max-age');
        }

        if ($request->getMethod() === 'HEAD') {
            if (!$this->headers->has('Content-Length')) {
                $this->headers->set('Content-Length', strlen($this->content));
            }
            $this->content = '';
        }

        if (!$this->headers->has('Content-Length')) {
            $this->headers->set('Content-Length', strlen($this->content));
        }

        if ($this->headers->has('Transfer-Encoding')) {
            $this->headers->remove('Content-Length');
        }
    }

    /**
     * Sends the HTTP headers.
     *
     * If headers have been sent previously, this method fails silently.
     *
     * @return void
     * @codeCoverageIgnore
     * @deprecated Since Flow 5.1, without replacement
     * TODO: Make private after deprecation period
     */
    public function sendHeaders()
    {
        if (headers_sent() === true) {
            return;
        }
        foreach ($this->renderHeaders() as $header) {
            header($header, false);
        }
        foreach ($this->headers->getCookies() as $cookie) {
            header('Set-Cookie: ' . $cookie, false);
        }
    }

    /**
     * Renders and sends the whole web response
     *
     * @return void
     * @codeCoverageIgnore
     * @api PSR-7
     */
    public function send()
    {
        $this->sendHeaders();
        if ($this->content !== null) {
            echo $this->getBody()->getContents();
        }
    }

    /**
     * Return the Status-Line of this Response Message, consisting of the version, the status code and the reason phrase
     * Would be, for example, "HTTP/1.1 200 OK" or "HTTP/1.1 400 Bad Request"
     *
     * @return string
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
     * @deprecated Since Flow 5.1
     * @see ResponseInformationHelper::generateStatusLine
     */
    public function getStatusLine()
    {
        return ResponseInformationHelper::generateStatusLine($this);
    }

    /**
     * Returns the first line of this Response Message, which is the Status-Line in this case
     *
     * @return string The Status-Line of this Response
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html chapter 4.1 "Message Types"
     * @deprecated Since Flow 5.1
     * @see ResponseInformationHelper::generateStatusLine
     */
    public function getStartLine()
    {
        return ResponseInformationHelper::generateStatusLine($this);
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return self
     * @throws \InvalidArgumentException For invalid status code arguments.
     * @api PSR-7
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $newResponse = clone $this;
        $newResponse->setStatus($code, ($reasonPhrase === '' ? null : $reasonPhrase));
        return $newResponse;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     * @api PSR-7
     */
    public function getReasonPhrase()
    {
        return $this->statusMessage;
    }

    /**
     * Cast the response to a string: return the content part of this response
     *
     * @return string The same as getContent(), an empty string if getContent() returns a value which can't be cast into a string
     * @api
     */
    public function __toString()
    {
        $output = $this->getBody()->getContents();
        if (is_object($output) || is_array($output)) {
            $output = '';
        }
        return (string)$output;
    }
}
