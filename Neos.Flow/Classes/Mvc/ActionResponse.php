<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Http\Response;
use Psr\Http\Message\UriInterface;

/**
 * The new minimal MVC response object.
 * For anything more use a custom HTTP component and set a component parameter.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class ActionResponse extends \Neos\Flow\Http\Response implements ActionResponseInterface
{
    /**
     * @var array
     */
    private $componentParameters = [];

    /**
     * @var UriInterface
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $contentType = '';

    /**
     * @param string $content
     * @return void
     * @api
     */
    public function setContent($content): void
    {
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
        // TODO: This can be removed after the full changes are done for next major.
        $this->headers->set('Content-Type', $contentType);
    }

    /**
     * Set a redirect URI and according status for this response.
     *
     * @param UriInterface $uri
     * @param int $statusCode
     * return void
     * @api
     */
    public function setRedirectUri(UriInterface $uri, int $statusCode = 303): void
    {
        $this->redirectUri = $uri;
        $this->statusCode = $statusCode;
        // TODO: This can be removed after the full changes are done for next major.
        $this->headers->set('Location', (string)$uri);
        $this->setStatusCode($statusCode);
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
        // TODO: This can be removed after the full changes are done for next major.
        $this->setStatusCode($statusCode);
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
     * Purely internal implementation to support backwards compatibility.
     *
     * @param string $rawResponse
     * @param Response $parentResponse Deprecated parameter. Parent response, if called recursively
     *
     * @return self
     * @throws \InvalidArgumentException
     * @internal
     * @deprecated
     * TODO: Can be removed when the ActionResponse no longer extends HTTP response.
     */
    public static function createFromRaw($rawResponse, Response $parentResponse = null)
    {
        /** @var Response $response */
        $response = ResponseInformationHelper::createFromRaw($rawResponse, self::class);
        $response->parentResponse = $parentResponse;

        return $response;
    }
}
