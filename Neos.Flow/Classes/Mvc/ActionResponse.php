<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Cookie;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\Component\SetHeaderComponent;
use Neos\Flow\Http\Component\ReplaceHttpResponseComponent;

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
     * @var array
     */
    protected $headers = [];

    /**
     * @var ResponseInterface|null
     */
    protected $httpResponse;

    public function __construct()
    {
        $this->content = stream_for();
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
    }

    /**
     * Set a (HTTP) component parameter for use later in the chain.
     * This can be used to adjust all aspects of the later processing if needed.
     *
     * @param string $componentClassName
     * @param string $parameterName
     * @param mixed $value
     * @return void
     * @deprecated since Flow 7.0 use setHttpHeader or replaceHttpResponse instead. For now this will still work with $componentClassName of "SetHeaderComponent" or "ReplaceHttpResponseComponent" only.
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
        throw new \InvalidArgumentException(sprintf('The method %s is deprecated. It will only allow a $componentClassName parameter of "%s" or "%s". If you want to send data to your middleware from the action, use response headers or introduce a global context. Both solutions are to be considered bad practice though.', __METHOD__, SetHeaderComponent::class, ReplaceHttpResponseComponent::class), 1605088079);
    }

    /**
     * Set the specified header in the response, overwriting any previous value set for this header.
     *
     * @param string $headerName The name of the header to set
     * @param array|string|\DateTime $headerValue An array of values or a single value for the specified header field
     * @return void
     */
    public function setHttpHeader(string $headerName, $headerValue): void
    {
        // This is taken from the Headers class, which should eventually replace this implementation and add more response API methods.
        if ($headerValue instanceof \DateTimeInterface) {
            $date = clone $headerValue;
            $date->setTimezone(new \DateTimeZone('GMT'));
            $headerValue = [$date->format(DATE_RFC2822)];
        }
        $this->headers[$headerName] = (array)$headerValue;
    }

    /**
     * Add the specified header to the response, without overwriting any previous value set for this header.
     *
     * @param string $headerName The name of the header to set
     * @param array|string|\DateTime $headerValue An array of values or a single value for the specified header field
     * @return void
     */
    public function addHttpHeader(string $headerName, $headerValue): void
    {
        if ($headerValue instanceof \DateTimeInterface) {
            $date = clone $headerValue;
            $date->setTimezone(new \DateTimeZone('GMT'));
            $headerValue = [$date->format(DATE_RFC2822)];
        }
        $this->headers[$headerName] = array_merge($this->headers[$headerName] ?? [], (array)$headerValue);
    }

    /**
     * Return the specified HTTP header that was previously set.
     *
     * @param string $headerName The name of the header to get the value(s) for
     * @return array|string|null An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
     */
    public function getHttpHeader(string $headerName)
    {
        if (!isset($this->headers[$headerName])) {
            return null;
        }

        return count($this->headers[$headerName]) > 1 ? $this->headers[$headerName] : reset($this->headers[$headerName]);
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
    public function getContentType(): ?string
    {
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
        foreach ($this->headers as $headerName => $headerValue) {
            $actionResponse->setHttpHeader($headerName, $headerValue);
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
}
