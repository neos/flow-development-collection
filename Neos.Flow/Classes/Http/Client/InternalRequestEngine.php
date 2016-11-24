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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Exception;
use Neos\Flow\Http\Component\ComponentChain;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Tests\FunctionalTestRequestHandler;
use Neos\Flow\Validation\ValidatorResolver;

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
     * @var Router
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
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Sends the given HTTP request
     *
     * @param Http\Request $httpRequest
     * @return Http\Response
     * @throws Http\Exception
     * @api
     */
    public function sendRequest(Http\Request $httpRequest)
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof FunctionalTestRequestHandler) {
            throw new Http\Exception('The browser\'s internal request engine has only been designed for use within functional tests.', 1335523749);
        }

        $this->securityContext->clearContext();
        $this->validatorResolver->reset();

        $response = new Http\Response();
        $componentContext = new ComponentContext($httpRequest, $response);
        $requestHandler->setComponentContext($componentContext);

        $objectManager = $this->bootstrap->getObjectManager();
        $baseComponentChain = $objectManager->get(\Neos\Flow\Http\Component\ComponentChain::class);
        $componentContext = new ComponentContext($httpRequest, $response);

        try {
            $baseComponentChain->handle($componentContext);
        } catch (\Throwable $throwable) {
            $this->prepareErrorResponse($throwable, $componentContext->getHttpResponse());
        } catch (\Exception $exception) {
            $this->prepareErrorResponse($exception, $componentContext->getHttpResponse());
        }
        $session = $this->bootstrap->getObjectManager()->get(SessionInterface::class);
        if ($session->isStarted()) {
            $session->close();
        }
        $this->persistenceManager->clearState();
        return $componentContext->getHttpResponse();
    }

    /**
     * Returns the router used by this internal request engine
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Prepare a response in case an error occurred.
     *
     * @param object $exception \Exception or \Throwable
     * @param Http\Response $response
     * @return void
     */
    protected function prepareErrorResponse($exception, Http\Response $response)
    {
        $pathPosition = strpos($exception->getFile(), 'Packages/');
        $filePathAndName = ($pathPosition !== false) ? substr($exception->getFile(), $pathPosition) : $exception->getFile();
        $exceptionCodeNumber = ($exception->getCode() > 0) ? '#' . $exception->getCode() . ': ' : '';
        $content = PHP_EOL . 'Uncaught Exception in Flow ' . $exceptionCodeNumber . $exception->getMessage() . PHP_EOL;
        $content .= 'thrown in file ' . $filePathAndName . PHP_EOL;
        $content .= 'in line ' . $exception->getLine() . PHP_EOL . PHP_EOL;
        $content .= Debugger::getBacktraceCode($exception->getTrace(), false, true) . PHP_EOL;

        if ($exception instanceof Exception) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }
        $response->setStatus($statusCode);
        $response->setContent($content);
        $response->setHeader('X-Flow-ExceptionCode', $exception->getCode());
        $response->setHeader('X-Flow-ExceptionMessage', $exception->getMessage());
    }
}
