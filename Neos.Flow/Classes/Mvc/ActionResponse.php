<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Cookie;
use Neos\Flow\Http\Headers;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Response;

/**
 * The minimal MVC response object.
 * It allows for simple interactions with the HTTP response from within MVC actions. More specific requirements can be implemented via HTTP middlewares.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class ActionResponse
{
    /**
     * @var Stream
     */
    protected $content;

    /**
     * @var UriInterface
     */
    protected $redirectUri;

    /**
     * The HTTP status code
     *
     * Note the getter has a default value,
     * but internally this can be null to signify a status code was never set explicitly.
     *
     * @var integer|null
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var Cookie[]
     */
    protected $cookies = [];

    /**
     * @var ResponseInterface|null
     */
    protected $httpResponse;

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var Headers
     */
    protected $headers;

    public function __construct()
    {
        $this->content = stream_for();
        $this->headers = new Headers();
    }

    /**
     * @param string|StreamInterface $content
     * @return void
     * @api
     */
    public function setContent($content): void
    {
        if (!$content instanceof StreamInterface) {
            $content = stream_for($content);
        }

        $this->content = $content;
    }

    /**
     * Set content mime type for this response.
     *
     * @param string $contentType
     * @return void
     * @api
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Set a redirect URI and according status for this response.
     *
     * @param UriInterface $uri
     * @param int $statusCode
     * @return void
     * @api
     */
    public function setRedirectUri(UriInterface $uri, int $statusCode = 303): void
    {
        $this->redirectUri = $uri;
        $this->statusCode = $statusCode;
    }

    /**
     * Set the status code for this response as HTTP status code.
     * Other codes than HTTP status may end in unpredictable results.
     *
     * @param int $statusCode
     * @return void
     * @api
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * Set a cookie in the HTTP response
     * This leads to a corresponding `Set-Cookie` header to be set in the HTTP response
     *
     * @param Cookie $cookie Cookie to be set in the HTTP response
     * @api
     */
    public function setCookie(Cookie $cookie): void
    {
        $this->cookies[$cookie->getName()] = clone $cookie;
        $this->headers->setCookie($cookie);
    }

    /**
     * Delete a cooke from the HTTP response
     * This leads to a corresponding `Set-Cookie` header with an expired Cookie to be set in the HTTP response
     *
     * @param string $cookieName Name of the cookie to delete
     * @api
     */
    public function deleteCookie(string $cookieName): void
    {
        $cookie = new Cookie($cookieName);
        $cookie->expire();
        $this->cookies[$cookie->getName()] = $cookie;
        $this->headers->deleteCookie($cookieName);
    }

    /**
     * Set a (HTTP) component parameter for use later in the chain.
     * This can be used to adjust all aspects of the later processing if needed.
     *
     * @param string $componentClassName
     * @param string $parameterName
     * @param mixed $value
     * @return void
     * @deprecated since Flow 7.0 use setHttpHeader or replaceHttpResponse instead. See also #linkToDocs
     */
    public function setComponentParameter(string $componentClassName, string $parameterName, $value): void
    {
        if ($componentClassName === SetHeaderComponent::class) {
            $this->setHttpHeader($parameterName, $value);
            return;
        }

        if ($componentClassName === ReplaceHttpResponseComponent::class && $parameterName === 'response') {
            $this->replaceHttpResponse($value);
            return;
        }
        throw new \InvalidArgumentException('todo');
    }

    /**
     * Set the specified header in the response, overwriting any previous value set for this header.
     *
     * @param string $headerName The name of the header to set
     * @param array|string|\DateTime $headerValue An array of values or a single value for the specified header field
     * @return void
     */
    public function setHttpHeader($headerName, $headerValue)
    {
        $this->headers->set($headerName, $headerValue);
    }

    /**
     * Add the specified header to the response, without overwriting any previous value set for this header.
     *
     * @param string $headerName The name of the header to set
     * @param array|string|\DateTime $headerValue An array of values or a single value for the specified header field
     * @return void
     */
    public function addHttpHeader($headerName, $headerValue)
    {
        $this->headers->set($headerName, $headerValue, false);
    }

    /**
     * Return the specified HTTP header that was previously set.
     * Dates are returned as DateTime objects with the timezone set to GMT.
     *
     * @param string $headerName The name of the header to get the value(s) for
     * @return array|string|\DateTime An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
     */
    public function getHttpHeader($headerName)
    {
        return $this->headers->get($headerName);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        $content = $this->content->getContents();
        $this->content->rewind();
        return $content;
    }

    /**
     * @return UriInterface
     */
    public function getRedirectUri(): ?UriInterface
    {
        return $this->redirectUri;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode ?? 200;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->headers->get('Content-Type');
        return $this->contentType;
    }

    /**
     * Use this if you want build your own HTTP Response inside your action
     * @param ResponseInterface $response
     */
    public function replaceHttpResponse(ResponseInterface $response): void
    {
        $this->httpResponse = $response;
    }

    /**
     * @param ActionResponse $actionResponse
     * @return ActionResponse
     */
    public function mergeIntoParentResponse(ActionResponse $actionResponse): ActionResponse
    {
        if ($this->hasContent()) {
            $actionResponse->setContent($this->content);
        }

        if ($this->contentType !== null) {
            $actionResponse->setContentType($this->contentType);
        }

        if ($this->statusCode !== null) {
            $actionResponse->setStatusCode($this->statusCode);
        }

        if ($this->redirectUri !== null) {
            $actionResponse->setRedirectUri($this->redirectUri);
        }

        if ($this->httpResponse !== null) {
            $actionResponse->replaceHttpResponse($this->httpResponse);
        }
        foreach ($this->cookies as $cookie) {
            $actionResponse->setCookie($cookie);
        }

        return $actionResponse;
    }

    /**
     * Note this is a special use case method that will apply the internal properties (Content-Type, StatusCode, Location, Set-Cookie and Content)
     * to the given PSR-7 Response and return a modified response. This is used to merge the ActionResponse properties into a possible HttpResponse
     * created in a View (see ActionController::renderView()) because those would be overwritten otherwise. Note that any component parameters will
     * still run through the component chain and will not be propagated here.
     *
     * @return ResponseInterface
     * @internal
     */
    public function buildHttpResponse(): ResponseInterface
    {
        $httpResponse = $this->httpResponse ?? new Response();

        if ($this->statusCode !== null) {
            $httpResponse = $httpResponse->withStatus($this->statusCode);
        }

        if ($this->hasContent()) {
            $httpResponse = $httpResponse->withBody($this->content);
        }

        if ($this->contentType !== null) {
            $httpResponse = $httpResponse->withHeader('Content-Type', $this->contentType);
        }

        if ($this->redirectUri !== null) {
            $httpResponse = $httpResponse->withHeader('Location', (string)$this->redirectUri);
        }

        foreach ($this->headers as $headerName => $headerValue) {
            $httpResponse = $httpResponse->withAddedHeader($headerName, implode(', ', $headerValue));
        }
        foreach ($this->cookies as $cookie) {
            $httpResponse = $httpResponse->withAddedHeader('Set-Cookie', (string)$cookie);
        }

        return $httpResponse;
    }

    /**
     * Does this action response have content?
     *
     * @return bool
     */
    private function hasContent(): bool
    {
        return $this->content->getSize() > 0;
    }

    /**
     * Sets the respective directive in the Cache-Control header.
     *
     * A response flagged as "public" may be cached by any cache, even if it normally
     * wouldn't be cacheable in a shared cache.
     */
    public function setPublic()
    {
        $this->headers->setCacheControlDirective('public');
    }

    /**
     * Sets the respective directive in the Cache-Control header.
     *
     * A response flagged as "private" tells that it is intended for a specific
     * user and must not be cached by a shared cache.
     */
    public function setPrivate()
    {
        $this->headers->setCacheControlDirective('private');
    }

    /**
     * Sets the Date header.
     *
     * The given date must either be an RFC2822 parseable date string or a DateTime
     * object. The timezone will be converted to GMT internally, but the point in
     * time remains the same.
     *
     * @param string|\DateTime $date
     */
    public function setDate($date)
    {
        $this->headers->set('Date', $date);
    }

    /**
     * Returns the date from the Date header.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime|null The date of this response
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
     */
    public function setLastModified($date)
    {
        $this->headers->set('Last-Modified', $date);
    }

    /**
     * Returns the date from the Last-Modified header or NULL if no such header
     * is present.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime|null The last modification date or NULL
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
     */
    public function setExpires($date)
    {
        $this->headers->set('Expires', $date);
    }

    /**
     * Returns the date from the Expires header or NULL if no such header
     * is present.
     *
     * The returned date is configured to be in the GMT timezone.
     *
     * @return \DateTime|null The expiration date or NULL
     */
    public function getExpires()
    {
        return $this->headers->get('Expires');
    }

    /**
     * Sets the maximum age in seconds before this response becomes stale.
     *
     * This method sets the "max-age" directive in the Cache-Control header.
     *
     * @param integer $age The maximum age in seconds
     */
    public function setMaximumAge($age)
    {
        $this->headers->setCacheControlDirective('max-age', $age);
    }

    /**
     * Returns the maximum age in seconds before this response becomes stale.
     *
     * This method returns the value from the "max-age" directive in the
     * Cache-Control header.
     *
     * @return integer The maximum age in seconds, or NULL if none has been defined
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
     */
    public function setSharedMaximumAge($maximumAge)
    {
        $this->headers->setCacheControlDirective('s-maxage', $maximumAge);
    }

    /**
     * Returns the maximum age in seconds before this response becomes stale in shared
     * caches, such as proxies.
     *
     * This method returns the value from the "s-maxage" directive in the
     * Cache-Control header.
     *
     * @return integer The maximum age in seconds, or NULL if none has been defined
     */
    public function getSharedMaximumAge()
    {
        return $this->headers->getCacheControlDirective('s-maxage');
    }
}
