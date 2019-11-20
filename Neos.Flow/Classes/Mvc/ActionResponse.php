<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Http\Cookie;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use GuzzleHttp\Psr7\Stream;

/**
 * The minimal MVC response object.
 * It allows for simple interactions with the HTTP response from within MVC actions. More specific requirements can be implemented via HTTP Components.
 * @see setComponentParameter()
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
     * @var array
     */
    protected $componentParameters = [];

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
     * @api
     */
    public function setComponentParameter(string $componentClassName, string $parameterName, $value): void
    {
        if (!isset($this->componentParameters[$componentClassName])) {
            $this->componentParameters[$componentClassName] = [];
        }
        $this->componentParameters[$componentClassName][$parameterName] = $value;
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
     * @return array
     */
    public function getComponentParameters(): array
    {
        return $this->componentParameters;
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
        return $this->contentType;
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

        foreach ($this->componentParameters as $componentClass => $parameters) {
            foreach ($parameters as $parameterName => $parameterValue) {
                $actionResponse->setComponentParameter($componentClass, $parameterName, $parameterValue);
            }
        }
        foreach ($this->cookies as $cookie) {
            $actionResponse->setCookie($cookie);
        }

        return $actionResponse;
    }

    /**
     * @param ComponentContext $componentContext
     * @return ComponentContext
     */
    public function mergeIntoComponentContext(ComponentContext $componentContext): ComponentContext
    {
        $httpResponse = $componentContext->getHttpResponse();
        if ($this->statusCode !== null) {
            $httpResponse = $httpResponse->withStatus($this->statusCode);
        }

        if ($this->hasContent()) {
            $httpResponse = $httpResponse->withBody($this->content);
        }

        if ($this->contentType) {
            $httpResponse = $httpResponse->withHeader('Content-Type', $this->contentType);
        }

        if ($this->redirectUri) {
            $httpResponse = $httpResponse->withHeader('Location', (string)$this->redirectUri);
        }

        foreach ($this->componentParameters as $componentClassName => $componentParameterGroup) {
            foreach ($componentParameterGroup as $parameterName => $parameterValue) {
                $componentContext->setParameter($componentClassName, $parameterName, $parameterValue);
            }
        }

        foreach ($this->cookies as $cookie) {
            $httpResponse = $httpResponse->withAddedHeader('Set-Cookie', (string)$cookie);
        }

        $componentContext->replaceHttpResponse($httpResponse);

        return $componentContext;
    }

    /**
     * Note this is a special use case method that will apply the internal properties (Content-Type, StatusCode, Location, Set-Cookie and Content)
     * to the given PSR-7 Response and return a modified response. This is used to merge the ActionResponse properties into a possible HttpResponse
     * created in a View (see ActionController::renderView()) because those would be overwritten otherwise. Note that any component parameters will
     * still run through the component chain and will not be propagated here.
     *
     * WARNING: Should this ActionResponse contain body content it would replace any content in the given HttpReponse.
     *
     * @param ResponseInterface $httpResponse
     * @return ResponseInterface
     * @internal
     */
    public function applyToHttpResponse(ResponseInterface $httpResponse): ResponseInterface
    {
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
