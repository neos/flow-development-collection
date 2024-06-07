<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Testing\RequestHandler;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Everything from {@see RuntimeSequenceInvokingRequestHandler} applies to this.
 *
 * Additionally, it also provides some support for HTTP request testing scenarios.
 * For that reason it features a setRequest() method which is used by the FunctionalTestCase
 * for setting the current HTTP request. That way, the request handler acts pretty much
 * like the Http\RequestHandler from a client code perspective.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class RuntimeSequenceHttpRequestHandler extends RuntimeSequenceInvokingRequestHandler implements HttpRequestHandlerInterface
{
    private ServerRequestInterface|null $httpRequest;

    /**
     * @param ServerRequestInterface $request
     */
    public function setHttpRequest(ServerRequestInterface $request): void
    {
        $this->httpRequest = $request;
    }

    /**
     * Returns the currently processed HTTP request
     *
     * @return ServerRequestInterface
     */
    public function getHttpRequest(): ServerRequestInterface
    {
        if ($this->httpRequest === null) {
            $this->httpRequest = ServerRequest::fromGlobals();
        }

        return $this->httpRequest;
    }
}
