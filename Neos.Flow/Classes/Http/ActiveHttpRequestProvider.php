<?php
namespace Neos\Flow\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Central authority to get hold of the active HTTP request.
 * When no active HTTP request can be determined (for example in CLI context) a new instance is built using a ServerRequestFactoryInterface implementation
 *
 * Note: Naturally this class is not being used explicitly. But it is configured as factory for Psr\Http\Message\ServerRequestInterface instances
 *
 * @Flow\Scope("singleton")
 */
final class ActiveHttpRequestProvider
{
    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var BaseUriProvider
     */
    protected $baseUriProvider;

    /**
     * @Flow\Inject
     * @var ServerRequestFactoryInterface
     */
    protected $serverRequestFactory;

    /**
     * Returns the currently active HTTP request, if available.
     * If the HTTP request can't be determined, a new instance is created using an instance of ServerRequestFactoryInterface
     *
     * @return ServerRequestInterface
     */
    public function getActiveHttpRequest(): ServerRequestInterface
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof HttpRequestHandlerInterface) {
            return $requestHandler->getHttpRequest();
        }
        try {
            $baseUri = $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest();
        } catch (Exception $e) {
            $baseUri = 'http://localhost';
        }
        return $this->serverRequestFactory->createServerRequest('GET', $baseUri);
    }
}
