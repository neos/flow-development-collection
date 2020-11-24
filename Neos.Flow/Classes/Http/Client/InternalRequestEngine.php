<?php
namespace Neos\Flow\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Exception as FlowException;
use Neos\Flow\Http;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\FlashMessage\FlashMessageService;
use Neos\Flow\Mvc\Routing\RouterInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Validation\ValidatorResolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * A Request Engine which uses Flow's request dispatcher directly for processing
 * HTTP requests internally.
 *
 * This engine is particularly useful in functional test scenarios.
 */
class InternalRequestEngine implements RequestEngineInterface
{
    /**
     * @Flow\Inject(lazy = false)
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject(lazy = false)
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @Flow\Inject(lazy = false)
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\Inject(lazy = false)
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var ValidatorResolver
     */
    protected $validatorResolver;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @Flow\Inject
     * @var StreamFactoryInterface
     */
    protected $contentFactory;

    /**
     * Sends the given HTTP request
     *
     * @param ServerRequestInterface $httpRequest
     * @return ResponseInterface
     * @throws FlowException
     * @throws Http\Exception
     * @api
     */
    public function sendRequest(ServerRequestInterface $httpRequest): ResponseInterface
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof FunctionalTestRequestHandler) {
            throw new Http\Exception('The browser\'s internal request engine has only been designed for use within functional tests.', 1335523749);
        }

        $requestHandler->setHttpRequest($httpRequest);
        $this->securityContext->clearContext();
        $this->validatorResolver->reset();

        $objectManager = $this->bootstrap->getObjectManager();
        $middlewaresChain = $objectManager->get(Http\Middleware\MiddlewaresChain::class);

        try {
            $response = $middlewaresChain->handle($httpRequest);
        } catch (\Throwable $throwable) {
            $response = $this->prepareErrorResponse($throwable, new Response());
        }
        $session = $objectManager->get(SessionInterface::class);
        if ($session->isStarted()) {
            $session->close();
        }
        // FIXME: ObjectManager should forget all instances created during the request
        $objectManager->forgetInstance(SessionManager::class);
        $objectManager->forgetInstance(FlashMessageService::class);
        $this->persistenceManager->clearState();
        return $response;
    }

    /**
     * Returns the router used by this internal request engine
     *
     * @return RouterInterface
     */
    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    /**
     * Prepare a response in case an error occurred.
     *
     * @param object $exception \Exception or \Throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function prepareErrorResponse($exception, ResponseInterface $response): ResponseInterface
    {
        $pathPosition = strpos($exception->getFile(), 'Packages/');
        $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
        $content = PHP_EOL . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
        $content .= 'thrown in file ' . $filePathAndName . PHP_EOL;
        $content .= 'in line ' . $exception->getLine() . PHP_EOL . PHP_EOL;
        $content .= Debugger::getBacktraceCode($exception->getTrace(), false, true) . PHP_EOL;

        if ($exception instanceof FlowException) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        return $response
            ->withStatus($statusCode)
            ->withBody($this->contentFactory->createStream($content))
            ->withHeader('X-Flow-ExceptionCode', $exception->getCode())
            ->withHeader('X-Flow-ExceptionMessage', $exception->getMessage());
    }
}
