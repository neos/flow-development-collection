<?php
namespace Neos\Flow\Mvc;

use Psr\Http\Message\UriInterface;

/**
 * Defines how a render for an ActionResponse looks like.
 *
 */
interface ActionReponseRendererInterface
{
    /**
     * @param $content
     */
    public function setContent($content): void;

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType): void;

    /**
     * @param string $redirectUri
     */
    public function setRedirectUri(UriInterface $redirectUri): void;

    /**
     * @param int $statusCode
     */
    public function setStatusCode(int $statusCode): void;

    /**
     * @param array $componentParameters
     */
    public function setComponentParameters(array $componentParameters): void;

    /**
     * @return mixed
     */
    public function render();
}
