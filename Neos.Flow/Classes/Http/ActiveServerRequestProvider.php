<?php
namespace Neos\Flow\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Central authority to get hold of the active HTTP request.
 *
 * When no active HTTP request can be determined (for example in CLI context) a new instance is built using a ServerRequestFactoryInterface implementation
 *
 * @Flow\Scope("singleton")
 */
final class ActiveServerRequestProvider
{
    /**
     * @Flow\Inject
     * @var Bootstrap
     */
    protected $bootstrap;

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
    public function getActiveServerRequest(): ServerRequestInterface
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if ($requestHandler instanceof HttpRequestHandlerInterface) {
            return $requestHandler->getHttpRequest();
        }
        return $this->serverRequestFactory->createServerRequest('GET', 'http://localhost');
    }
}
