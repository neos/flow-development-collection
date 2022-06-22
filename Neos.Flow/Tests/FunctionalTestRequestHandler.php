<?php
namespace Neos\Flow\Tests;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A request handler which boots up Flow into a basic runtime level and then returns
 * without actually further handling command line commands.
 *
 * As this request handler will be the "active" request handler returned by
 * the bootstrap's getActiveRequestHandler() method, it also needs some support
 * for HTTP request testing scenarios. For that reason it features a setRequest()
 * method which is used by the FunctionalTestCase for setting the current HTTP
 * request. That way, the request handler acts pretty much like the Http\RequestHandler
 * from a client code perspective.
 *
 * The virtual browser's InternalRequestEngine will also set the current request
 * via the setRequest() method.
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class FunctionalTestRequestHandler implements \Neos\Flow\Http\HttpRequestHandlerInterface
{
    /**
     * @var \Neos\Flow\Core\Bootstrap
     */
    protected $bootstrap;

    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

    /**
     * Constructor
     *
     * @param \Neos\Flow\Core\Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * This request handler can handle requests in Testing Context.
     *
     * @return boolean If the context is Testing, true otherwise false
     */
    public function canHandleRequest()
    {
        return $this->bootstrap->getContext()->isTesting();
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * As this request handler can only be used as a preselected request handler,
     * the priority for all other cases is 0.
     *
     * @return integer The priority of the request handler.
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * Handles a command line request
     *
     * @return void
     */
    public function handleRequest()
    {
        $sequence = $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);
    }

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
