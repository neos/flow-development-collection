<?php
namespace Neos\Flow\Mvc;

use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

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
     * @var string
     */
    private $content;

    /**
     * @var array
     */
    private $componentParameters = [];

    /**
     * @var UriInterface
     */
    private $redirectUri;

    /**
     * The HTTP status code
     *
     * @var integer
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @param string|StreamInterface $content
     * @return void
     * @api
     */
    public function setContent($content): void
    {
        // TODO: For next major specific handling of StreamInterface arguments should be done to keep them intact.
        $this->content = (string)$content;
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
//        $this->headers->set('Content-Type', $contentType, true);
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
        // TODO: This can be removed after the full changes are done for next major.
//        $this->headers->set('Location', (string)$uri, true);
//        $this->setStatusCode($statusCode);
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
     * @param ActionReponseRendererInterface $renderer
     * @return ActionReponseRendererInterface
     */
    public function prepareRendering(ActionReponseRendererInterface $renderer): ActionReponseRendererInterface
    {
        $renderer->setContent($this->content);
        $renderer->setContentType($this->contentType);
        $renderer->setRedirectUri($this->redirectUri);
        $renderer->setStatusCode($this->statusCode);
        $renderer->setComponentParameters($this->componentParameters);

        return $renderer;
    }
}
