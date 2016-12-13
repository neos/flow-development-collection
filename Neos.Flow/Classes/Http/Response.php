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

use Neos\Flow\Mvc\ResponseInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Represents an HTTP Response
 *
 * @api
 * @Flow\Proxy(false)
 */
class Response extends AbstractMessage implements ResponseInterface
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
     */
    public static function getStatusMessageByCode($statusCode)
    {
        $statusMessages = [
                100 => 'Continue',
                101 => 'Switching Protocols',
                102 => 'Processing', // RFC 2518
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-Status',
                300 => 'Multiple Choices',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',
                400 => 'Bad Request',
                401 => 'Unauthorized',
                402 => 'Payment Required',
                403 => 'Forbidden',
                404 => 'Not Found',
                405 => 'Method Not Allowed',
                406 => 'Not Acceptable',
                407 => 'Proxy Authentication Required',
                408 => 'Request Timeout',
                409 => 'Conflict',
                410 => 'Gone',
                411 => 'Length Required',
                412 => 'Precondition Failed',
                413 => 'Request Entity Too Large',
                414 => 'Request-URI Too Long',
                415 => 'Unsupported Media Type',
                416 => 'Requested Range Not Satisfiable',
                417 => 'Expectation Failed',
                418 => 'Sono Vibiemme',
                500 => 'Internal Server Error',
                501 => 'Not Implemented',
                502 => 'Bad Gateway',
                503 => 'Service Unavailable',
                504 => 'Gateway Timeout',
                505 => 'HTTP Version Not Supported',
                507 => 'Insufficient Storage',
                509 => 'Bandwidth Limit Exceeded',
        ];
        return isset($statusMessages[$statusCode]) ? $statusMessages[$statusCode] : 'Unknown Status';
    }

    /**
     * Construct this Response
     *
     * @param Response $parentResponse
     */
    public function __construct(Response $parentResponse = null)
    {
        $this->headers = new Headers();
        $this->headers->set('Content-Type', 'text/html; charset=' . $this->charset);
        $this->parentResponse = $parentResponse;
    }

    /**
     * Creates a response from the given raw, that is plain text, HTTP response.
     *
     * @param string $rawResponse
     * @param Response $parentResponse Parent response, if called recursively
     *
     * @throws \InvalidArgumentException
     * @return Response
     */
    public static function createFromRaw($rawResponse, Response $parentResponse = null)
    {
        $response = new static($parentResponse);

        $lines = explode(chr(10), $rawResponse);
        $statusLine = array_shift($lines);

        if (substr($statusLine, 0, 5) !== 'HTTP/') {
            throw new \InvalidArgumentException('The given raw HTTP message is not a valid response.', 1335175601);
        }
        list($version, $statusCode, $reasonPhrase) = explode(' ', $statusLine, 3);
        $response->setVersion($version);
        $response->setStatus((integer)$statusCode, trim($reasonPhrase));

        $parsingHeader = true;
        $contentLines = [];
        $headers = new Headers();
        foreach ($lines as $line) {
            if ($parsingHeader) {
                if (trim($line) === '') {
                    $parsingHeader = false;
                    continue;
                }
                $fieldName = trim(substr($line, 0, strpos($line, ':')));
                $fieldValue = trim(substr($line, strlen($fieldName) + 1));
                if (strtoupper(substr($fieldName, 0, 10)) === 'SET-COOKIE') {
                    $cookie = Cookie::createFromRawSetCookieHeader($fieldValue);
                    if ($cookie !== null) {
                        $headers->setCookie($cookie);
                    }
                } else {
                    $headers->set($fieldName, $fieldValue, false);
                }
            } else {
                $contentLines[] = $line;
            }
        }
        $content = implode(chr(10), $contentLines);

        $response->setHeaders($headers);
        $response->setContent($content);
        return $response;
    }

    /**
     * Return the parent response or NULL if none exists.
     *
     * @return Response the parent response, or NULL if none
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
     * @api
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
     * @api
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
     * @api
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
        $this->statusMessage = ($message === null) ? self::$statusMessages[$code] : $message;
        return $this;
    }

    /**
     * Returns status code and status message.
     *
     * @return string The status code and status message, eg. "404 Not Found"
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
     */
    public function getSharedMaximumAge()
    {
        return $this->headers->getCacheControlDirective('s-maxage');
    }

    /**
     * Renders the HTTP headers - including the status header - of this response
     *
     * @return array The HTTP headers
     * @api
     */
    public function renderHeaders()
    {
        $preparedHeaders = [];
        $statusHeader = rtrim($this->getStatusLine(), "\r\n");

        $preparedHeaders[] = $statusHeader;
        foreach ($this->headers->getAll() as $name => $values) {
            foreach ($values as $value) {
                $preparedHeaders[] = $name . ': ' . $value;
            }
        }

        return $preparedHeaders;
    }

    /**
     * Sets the respective directive in the Cache-Control header.
     *
     * A response flagged as "public" may be cached by any cache, even if it normally
     * wouldn't be cacheable in a shared cache.
     *
     * @return Response This response, for method chaining
     * @api
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
     * @api
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
     * @api
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
     * @api
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
     * @api
     */
    public function send()
    {
        $this->sendHeaders();
        if ($this->content !== null) {
            echo $this->getContent();
        }
    }

    /**
     * Return the Status-Line of this Response Message, consisting of the version, the status code and the reason phrase
     * Would be, for example, "HTTP/1.1 200 OK" or "HTTP/1.1 400 Bad Request"
     *
     * @return string
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html#sec6.1
     * @api
     */
    public function getStatusLine()
    {
        return sprintf("%s %s %s\r\n", $this->version, $this->statusCode, $this->statusMessage);
    }

    /**
     * Returns the first line of this Response Message, which is the Status-Line in this case
     *
     * @return string The Status-Line of this Response
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html chapter 4.1 "Message Types"
     * @api
     */
    public function getStartLine()
    {
        return $this->getStatusLine();
    }

    /**
     * Cast the response to a string: return the content part of this response
     *
     * @return string The same as getContent(), an empty string if getContent() returns a value which can't be cast into a string
     * @api
     */
    public function __toString()
    {
        $output = $this->getContent();
        if (is_object($output) || is_array($output)) {
            $output = '';
        }
        return (string)$output;
    }
}
