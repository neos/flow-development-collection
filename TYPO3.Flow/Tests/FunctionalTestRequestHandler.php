<?php
namespace TYPO3\Flow\Tests;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;

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
class FunctionalTestRequestHandler implements \TYPO3\Flow\Http\HttpRequestHandlerInterface
{
    /**
     * @var \TYPO3\Flow\Core\Bootstrap
     */
    protected $bootstrap;

    /**
     * @var \TYPO3\Flow\Http\Request
     */
    protected $httpRequest;

    /**
     * @var \TYPO3\Flow\Http\Response
     */
    protected $httpResponse;

    /**
     * Constructor
     *
     * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * This request handler can handle CLI requests.
     *
     * @return boolean If the request is a CLI request, TRUE otherwise FALSE
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
     * @return \TYPO3\Flow\Http\Request
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return \TYPO3\Flow\Http\Response
     * @api
     */
    public function getHttpResponse()
    {
        return $this->httpResponse;
    }

    /**
     * Allows to set the currently processed HTTP request by the base functional
     * test case.
     *
     * @param \TYPO3\Flow\Http\Request $request
     * @return void
     * @see InternalRequestEngine::sendRequest()
     */
    public function setHttpRequest(\TYPO3\Flow\Http\Request $request)
    {
        $this->httpRequest = $request;
    }

    /**
     * Allows to set the currently processed HTTP response by the base functional
     * test case.
     *
     * @param \TYPO3\Flow\Http\Response $response
     * @return void
     * @see InternalRequestEngine::sendRequest()
     */
    public function setHttpResponse(\TYPO3\Flow\Http\Response $response)
    {
        $this->httpResponse = $response;
    }
}
