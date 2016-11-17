<?php
namespace TYPO3\Flow\Mvc;

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
use TYPO3\Flow\Cli\Request as CliRequest;
use TYPO3\Flow\Configuration\Exception\NoSuchOptionException;
use TYPO3\Flow\Http\Response as HttpResponse;
use TYPO3\Flow\Log\SecurityLoggerInterface;
use TYPO3\Flow\Mvc\Controller\ControllerInterface;
use TYPO3\Flow\Mvc\Controller\Exception\InvalidControllerException;
use TYPO3\Flow\Mvc\Exception\InfiniteLoopException;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Mvc\Exception\ForwardException;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authentication\EntryPoint\WebRedirect;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Authorization\FirewallInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\AccessDeniedException;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;

/**
 * Dispatches requests to the controller which was specified by the request and
 * returns the response the controller generated.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Dispatcher
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Inject the Object Manager through setter injection because property injection
     * is not available during compile time.
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the Flow settings
     *
     * @param array $settings The Flow settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Dispatches a request to a controller
     *
     * @param RequestInterface $request The request to dispatch
     * @param ResponseInterface $response The response, to be modified by the controller
     * @return void
     * @throws AuthenticationRequiredException|AccessDeniedException
     * @api
     */
    public function dispatch(RequestInterface $request, ResponseInterface $response)
    {
        if ($request instanceof CliRequest) {
            $this->initiateDispatchLoop($request, $response);
            return;
        }

        // NOTE: The dispatcher is used for both Action- and CLI-Requests. For the latter case dispatching might happen during compile-time, that's why we can't inject the following dependencies

        /** @var Context $securityContext */
        $securityContext = $this->objectManager->get(Context::class);
        if ($securityContext->areAuthorizationChecksDisabled()) {
            $this->initiateDispatchLoop($request, $response);
            return;
        }

        /** @var FirewallInterface $firewall */
        $firewall = $this->objectManager->get(FirewallInterface::class);
        /** @var SecurityLoggerInterface $securityLogger */
        $securityLogger = $this->objectManager->get(SecurityLoggerInterface::class);

        try {
            /** @var ActionRequest $request */
            $firewall->blockIllegalRequests($request);
            $this->initiateDispatchLoop($request, $response);
        } catch (AuthenticationRequiredException $exception) {
            $entryPointFound = false;
            /** @var $token TokenInterface */
            foreach ($securityContext->getAuthenticationTokens() as $token) {
                $entryPoint = $token->getAuthenticationEntryPoint();
                if ($entryPoint === null) {
                    continue;
                }
                $entryPointFound = true;
                if ($entryPoint instanceof WebRedirect) {
                    $securityLogger->log('Redirecting to authentication entry point', LOG_INFO, $entryPoint->getOptions());
                } else {
                    $securityLogger->log(sprintf('Starting authentication with entry point of type "%s"', get_class($entryPoint)), LOG_INFO);
                }
                $securityContext->setInterceptedRequest($request->getMainRequest());
                /** @var HttpResponse $response */
                $entryPoint->startAuthentication($request->getHttpRequest(), $response);
            }
            if ($entryPointFound === false) {
                $securityLogger->log('No authentication entry point found for active tokens, therefore cannot authenticate or redirect to authentication automatically.', LOG_NOTICE);
                throw $exception;
            }
        } catch (AccessDeniedException $exception) {
            $securityLogger->log('Access denied', LOG_WARNING);
            throw $exception;
        }
    }

    /**
     * Try processing the request until it is successfully marked "dispatched"
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @throws InvalidControllerException|InfiniteLoopException|NoSuchOptionException
     */
    protected function initiateDispatchLoop(RequestInterface $request, ResponseInterface $response)
    {
        $dispatchLoopCount = 0;
        /** @var ActionRequest $request */
        while (!$request->isDispatched()) {
            if ($dispatchLoopCount++ > 99) {
                throw new Exception\InfiniteLoopException(sprintf('Could not ultimately dispatch the request after %d iterations.', $dispatchLoopCount), 1217839467);
            }
            $controller = $this->resolveController($request);
            try {
                $this->emitBeforeControllerInvocation($request, $response, $controller);
                $controller->processRequest($request, $response);
                $this->emitAfterControllerInvocation($request, $response, $controller);
            } catch (StopActionException $exception) {
                $this->emitAfterControllerInvocation($request, $response, $controller);
                if ($exception instanceof ForwardException) {
                    $request = $exception->getNextRequest();
                } elseif (!$request->isMainRequest()) {
                    $request = $request->getParentRequest();
                }
            }
        }
    }

    /**
     * This signal is emitted directly before the request is been dispatched to a controller.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param ControllerInterface $controller
     * @return void
     * @Flow\Signal
     */
    protected function emitBeforeControllerInvocation(RequestInterface $request, ResponseInterface $response, ControllerInterface $controller)
    {
    }

    /**
     * This signal is emitted directly after the request has been dispatched to a controller and the controller
     * returned control back to the dispatcher.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param ControllerInterface $controller
     * @return void
     * @Flow\Signal
     */
    protected function emitAfterControllerInvocation(RequestInterface $request, ResponseInterface $response, ControllerInterface $controller)
    {
    }

    /**
     * Finds and instantiates a controller that matches the current request.
     * If no controller can be found, an instance of NotFoundControllerInterface is returned.
     *
     * @param RequestInterface $request The request to dispatch
     * @return ControllerInterface
     * @throws NoSuchOptionException
     * @throws Controller\Exception\InvalidControllerException
     */
    protected function resolveController(RequestInterface $request)
    {
        /** @var ActionRequest $request */
        $controllerObjectName = $request->getControllerObjectName();
        if ($controllerObjectName === '') {
            if (isset($this->settings['mvc']['notFoundController'])) {
                throw new NoSuchOptionException('The configuration option TYPO3.Flow:mvc:notFoundController is deprecated since Flow 2.0. Use the "renderingGroups" option of the production exception handler instead in order to render custom error messages.', 1346949795);
            }
            $exceptionMessage = 'No controller could be resolved which would match your request';
            if ($request instanceof ActionRequest) {
                $exceptionMessage .= sprintf('. Package key: "%s", controller name: "%s"', $request->getControllerPackageKey(), $request->getControllerName());
                if ($request->getControllerSubpackageKey() !== null) {
                    $exceptionMessage .= sprintf(', SubPackage key: "%s"', $request->getControllerSubpackageKey());
                }
                $exceptionMessage .= sprintf('. (%s %s)', $request->getHttpRequest()->getMethod(), $request->getHttpRequest()->getUri());
            }
            throw new Controller\Exception\InvalidControllerException($exceptionMessage, 1303209195, null, $request);
        }

        $controller = $this->objectManager->get($controllerObjectName);
        if (!$controller instanceof ControllerInterface) {
            throw new Controller\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must be a valid request handling controller, ' . (is_object($controller) ? get_class($controller) : gettype($controller)) . ' given.', 1202921619, null, $request);
        }
        return $controller;
    }
}
