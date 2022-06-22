<?php
namespace Neos\Flow\Mvc;

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
use Neos\Flow\Configuration\Exception\NoSuchOptionException;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Controller\ControllerInterface;
use Neos\Flow\Mvc\Controller\Exception\InvalidControllerException;
use Neos\Flow\Mvc\Exception\InfiniteLoopException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\FirewallInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Exception\MissingConfigurationException;

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
     * @var Context
     */
    protected $securityContext;

    /**
     * @var FirewallInterface
     */
    protected $firewall;

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
     * @param Context $context
     */
    public function injectSecurityContext(Context $context)
    {
        $this->securityContext = $context;
    }

    /**
     * @param FirewallInterface $firewall
     */
    public function injectFirewall(FirewallInterface $firewall)
    {
        $this->firewall = $firewall;
    }

    /**
     * Dispatches a request to a controller
     *
     * @param ActionRequest $request The request to dispatch
     * @param ActionResponse $response The response, to be modified by the controller
     * @return void
     * @throws AccessDeniedException
     * @throws AuthenticationRequiredException
     * @throws InfiniteLoopException
     * @throws InvalidControllerException
     * @throws NoSuchOptionException
     * @throws MissingConfigurationException
     * @api
     */
    public function dispatch(ActionRequest $request, ActionResponse $response)
    {
        try {
            if ($this->securityContext->areAuthorizationChecksDisabled() !== true) {
                $this->firewall->blockIllegalRequests($request);
            }
            $this->initiateDispatchLoop($request, $response);
        } catch (AuthenticationRequiredException $exception) {
            // Rethrow as the SecurityEntryPoint middleware will take care of the rest
            throw $exception->attachInterceptedRequest($request);
        } catch (AccessDeniedException $exception) {
            /** @var PsrLoggerFactoryInterface $securityLogger */
            $securityLogger = $this->objectManager->get(PsrLoggerFactoryInterface::class)->get('securityLogger');
            $securityLogger->warning('Access denied', LogEnvironment::fromMethodName(__METHOD__));
            throw $exception;
        }
    }

    /**
     * Try processing the request until it is successfully marked "dispatched"
     *
     * @param ActionRequest $request
     * @param ActionResponse $parentResponse
     * @return ActionResponse
     * @throws InvalidControllerException|InfiniteLoopException|NoSuchOptionException|UnsupportedRequestTypeException
     */
    protected function initiateDispatchLoop(ActionRequest $request, ActionResponse $parentResponse)
    {
        $dispatchLoopCount = 0;
        while ($request !== null && $request->isDispatched() === false) {
            if ($dispatchLoopCount++ > 99) {
                throw new Exception\InfiniteLoopException(sprintf('Could not ultimately dispatch the request after %d iterations.', $dispatchLoopCount), 1217839467);
            }
            $controller = $this->resolveController($request);
            $response = new ActionResponse();
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
            $parentResponse = $response->mergeIntoParentResponse($parentResponse);
        }
        return $parentResponse;
    }

    /**
     * This signal is emitted directly before the request is been dispatched to a controller.
     *
     * @param ActionRequest $request
     * @param ActionResponse $response
     * @param ControllerInterface $controller
     * @return void
     * @Flow\Signal
     */
    protected function emitBeforeControllerInvocation(ActionRequest $request, ActionResponse $response, ControllerInterface $controller)
    {
    }

    /**
     * This signal is emitted directly after the request has been dispatched to a controller and the controller
     * returned control back to the dispatcher.
     *
     * @param ActionRequest $request
     * @param ActionResponse $response
     * @param ControllerInterface $controller
     * @return void
     * @Flow\Signal
     */
    protected function emitAfterControllerInvocation(ActionRequest $request, ActionResponse $response, ControllerInterface $controller)
    {
    }

    /**
     * Finds and instantiates a controller that matches the current request.
     * If no controller can be found, an instance of NotFoundControllerInterface is returned.
     *
     * @param ActionRequest $request The request to dispatch
     * @return ControllerInterface
     * @throws NoSuchOptionException
     * @throws Controller\Exception\InvalidControllerException
     */
    protected function resolveController(ActionRequest $request)
    {
        /** @var ActionRequest $request */
        $controllerObjectName = $request->getControllerObjectName();
        if ($controllerObjectName === '') {
            $exceptionMessage = 'No controller could be resolved which would match your request';
            if ($request instanceof ActionRequest) {
                $exceptionMessage .= sprintf('. Package key: "%s", controller name: "%s"', $request->getControllerPackageKey(), $request->getControllerName());
                if ($request->getControllerSubpackageKey() !== null) {
                    $exceptionMessage .= sprintf(', SubPackage key: "%s"', (string)$request->getControllerSubpackageKey());
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
