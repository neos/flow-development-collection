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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http;

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
     * @var ComponentContext
     */
    protected $componentContext;

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
     * @return boolean If the context is Testing, TRUE otherwise FALSE
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
     * Returns the currently processed HTTP request
     *
     * @return \Neos\Flow\Http\Request
     */
    public function getHttpRequest()
    {
        return $this->getComponentContext()->getHttpRequest();
    }

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return \Neos\Flow\Http\Response
     * @api
     */
    public function getHttpResponse()
    {
        return $this->getComponentContext()->getHttpResponse();
    }

    /**
     * Allows to set the currently processed HTTP component chain context by the base functional
     * test case.
     *
     * @param ComponentContext $context
     * @return void
     * @see InternalRequestEngine::sendRequest()
     */
    public function setComponentContext(ComponentContext $context)
    {
        $this->componentContext = $context;
    }

    /**
     * Internal getter to ensure an existing ComponentContext.
     *
     * @return ComponentContext
     */
    protected function getComponentContext()
    {
        if ($this->componentContext === null) {
            $this->componentContext = new ComponentContext(Http\Request::createFromEnvironment(), new Http\Response());
        }
        return $this->componentContext;
    }

    /**
     * Allows to set the currently processed HTTP request by the base functional
     * test case.
     *
     * @param \Neos\Flow\Http\Request $request
     * @return void
     * @deprecated since Flow 3.3, use setComponentContext() instead
     */
    public function setHttpRequest(\Neos\Flow\Http\Request $request)
    {
        $this->getComponentContext()->replaceHttpRequest($request);
    }

    /**
     * Allows to set the currently processed HTTP response by the base functional
     * test case.
     *
     * @param \Neos\Flow\Http\Response $response
     * @return void
     * @deprecated since Flow 3.3, use setComponentContext() instead
     */
    public function setHttpResponse(\Neos\Flow\Http\Response $response)
    {
        $this->getComponentContext()->replaceHttpResponse($response);
    }
}
