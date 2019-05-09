<?php
namespace Neos\Flow\Mvc\ActionResponseRenderer;

use Psr\Http\Message\UriInterface;

/**
 * Trait BasicSetterTrait
 */
trait BasicSetterTrait
{
    /**
     * @var
     */
    private $content;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var UriInterface
     */
    private $redirectUri;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
     */
    private $componentParameters;

    /**
     * @param $content
     */
    public function setContent($content): void
    {
        $this->content = $content;
    }

    public function setContentType(string $contentType = null): void
    {
        $this->contentType = $contentType;
    }

    public function setRedirectUri(UriInterface $redirectUri = null): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function setStatusCode(int $statusCode = null): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @param array $componentParameters
     */
    public function setComponentParameters(array $componentParameters = []): void
    {
        $this->componentParameters = $componentParameters;
    }
}
