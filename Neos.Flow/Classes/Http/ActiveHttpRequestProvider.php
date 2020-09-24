<?php
namespace Neos\Flow\Http;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Central authority to get hold of the active HTTP request.
 * When no active HTTP request can be determined (for example in CLI context) an exception will be thrown
 *
 * Note: Usually this class is not being used directly. But it is configured as factory for Psr\Http\Message\ServerRequestInterface instances
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
     * Returns the currently active HTTP request, if available.
     * If the HTTP request can't be determined (for example in CLI context) a \Neos\Flow\Http\Exception is thrown
     *
     * @return ServerRequestInterface
     * @throws Exception
     */
    public function getActiveHttpRequest(): ServerRequestInterface
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof HttpRequestHandlerInterface) {
            throw new Exception(sprintf('The active HTTP request can\'t be determined because the request handler is not an instance of %s but a %s. Use a ServerRequestFactory to create a HTTP requests in CLI context', HttpRequestHandlerInterface::class, get_class($requestHandler)), 1600869131);
        }
        return $requestHandler->getHttpRequest();
    }
}
