<?php
namespace TYPO3\Flow\Http\Client;

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
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Error\Debugger;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Http\Component\ComponentChain;
use TYPO3\Flow\Http\Component\ComponentContext;
use TYPO3\Flow\Http;
use TYPO3\Flow\Mvc\Dispatcher;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Session\SessionInterface;
use TYPO3\Flow\Tests\FunctionalTestRequestHandler;
use TYPO3\Flow\Validation\ValidatorResolver;

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
        $requestHandler->setHttpRequest($httpRequest);
        $requestHandler->setHttpResponse($response);

        $objectManager = $this->bootstrap->getObjectManager();
        $baseComponentChain = $objectManager->get(ComponentChain::class);
        $componentContext = new ComponentContext($httpRequest, $response);

        try {
            $baseComponentChain->handle($componentContext);
        } catch (\Throwable $throwable) {
            $this->prepareErrorResponse($throwable, $response);
        } catch (\Exception $exception) {
            $this->prepareErrorResponse($exception, $response);
        }
        $session = $this->bootstrap->getObjectManager()->get(SessionInterface::class);
        if ($session->isStarted()) {
            $session->close();
        }
        return $response;
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
