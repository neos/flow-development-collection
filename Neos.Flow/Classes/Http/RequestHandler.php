<?php
namespace Neos\Flow\Http;

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
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A request handler which can handle HTTP requests.
 *
 * @Flow\Scope("singleton")
 */
class RequestHandler implements HttpRequestHandlerInterface
{
    /**
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @var Middleware\MiddlewaresChain
     */
    protected $middlewaresChain;

    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

    /**
     * @var ResponseInterface
     */
    protected $httpResponse;

    /**
     * Make exit() a closure so it can be manipulated during tests
     *
     * @var \Closure
     */
    public $exit;

    /**
     * @param Bootstrap $bootstrap
     */
    public function __construct(Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->exit = function () {
            exit();
        };
    }

    /**
     * This request handler can handle any web request.
     *
     * @return boolean If the request is a web request, true otherwise false
     * @api
     */
    public function canHandleRequest()
    {
        return (PHP_SAPI !== 'cli');
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return integer The priority of the request handler.
     * @api
     */
    public function getPriority()
    {
        return 100;
    }

    /**
     * Handles a HTTP request
     *
     * @return void
     */
    public function handleRequest()
    {
        // Create the request very early so the ResourceManagement has a chance to grab it:
        $this->httpRequest = ServerRequest::fromGlobals();

        $this->boot();
        $this->resolveDependencies();

        $this->middlewaresChain->onStep(function (ServerRequestInterface $request) {
            $this->httpRequest = $request;
        });
        $this->httpResponse = $this->middlewaresChain->handle($this->httpRequest);

        $this->sendResponse($this->httpResponse);
        $this->bootstrap->shutdown(Bootstrap::RUNLEVEL_RUNTIME);
        $this->exit->__invoke();
    }

    /**
     * Returns the currently handled HTTP request
     *
     * @return ServerRequestInterface
     * @api
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    /**
     * Returns the HTTP response corresponding to the currently handled request
     *
     * @return ResponseInterface|null
     * @deprecated since 6.0. Don't depend on this method. The HTTP response only exists after the innermost middleware (dispatch) is done. For that stage use a middleware instead.
     */
    public function getHttpResponse()
    {
        throw new \BadMethodCallException(sprintf('The method %s was removed with Flow version 7.0 since its behavior is unreliable. To get hold of the response a middleware should be used instead.', __METHOD__), 1606467754);
    }

    /**
     * Boots up Flow to runtime
     *
     * @return void
     */
    protected function boot()
    {
        $sequence = $this->bootstrap->buildRuntimeSequence();
        $sequence->invoke($this->bootstrap);
    }

    /**
     * Resolves a few dependencies of this request handler which can't be resolved
     * automatically due to the early stage of the boot process this request handler
     * is invoked at.
     *
     * @return void
     */
    protected function resolveDependencies()
    {
        $objectManager = $this->bootstrap->getObjectManager();
        $this->middlewaresChain = $objectManager->get(Middleware\MiddlewaresChain::class);
    }

    /**
     * Send the HttpResponse of the component context to the browser and flush all output buffers.
     * @param ResponseInterface $response
     */
    protected function sendResponse(ResponseInterface $response)
    {
        ob_implicit_flush(1);
        foreach (ResponseInformationHelper::prepareHeaders($response) as $prepareHeader) {
            header($prepareHeader, false);
        }
        // Flush and stop all output buffers before sending the whole body in one go, as output buffering has no use any more
        // and just makes sending large files impossible without running out of memory
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $body = $response->getBody()->detach() ?: $response->getBody()->getContents();
        if (is_resource($body)) {
            fpassthru($body);
            fclose($body);
        } else {
            echo $body;
        }
    }
}
