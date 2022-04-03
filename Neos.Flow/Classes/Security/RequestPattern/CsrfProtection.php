<?php
namespace Neos\Flow\Security\RequestPattern;

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
use Neos\Flow\Http\Helper\SecurityHelper;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\RequestPatternInterface;
use Psr\Log\LoggerInterface;

/**
 * This class holds a request pattern that decides, if csrf protection was enabled for the current request and searches
 * for invalid csrf protection tokens.
 */
class CsrfProtection implements RequestPatternInterface
{
    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Matches an ActionRequest against the configured CSRF pattern rules and
     * searches for invalid csrf tokens. If this returns true, the request is invalid!
     *
     * @param ActionRequest $request The request that should be matched
     * @return boolean true if the pattern matched, false otherwise
     * @throws AuthenticationRequiredException
     */
    public function matchRequest(ActionRequest $request)
    {
        if (SecurityHelper::hasSafeMethod($request->getHttpRequest())) {
            $this->logger->debug('CSRF: No token required, safe request');
            return false;
        }
        if ($this->authenticationManager->isAuthenticated() === false) {
            $this->logger->debug('CSRF: No token required, not authenticated');
            return false;
        }
        if ($this->securityContext->areAuthorizationChecksDisabled() === true) {
            $this->logger->debug('CSRF: No token required, authorization checks are disabled');
            return false;
        }

        $controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
        $actionMethodName = $request->getControllerActionName() . 'Action';

        if (!$this->hasPolicyEntryForMethod($controllerClassName, $actionMethodName)) {
            $this->logger->debug(sprintf('CSRF: No token required, method %s::%s() is not restricted by a policy.', $controllerClassName, $actionMethodName));
            return false;
        }
        if ($this->reflectionService->isMethodTaggedWith($controllerClassName, $actionMethodName, 'skipcsrfprotection')) {
            $this->logger->debug(sprintf('CSRF: No token required, method %s::%s() is tagged with a "skipcsrfprotection" annotation', $controllerClassName, $actionMethodName));
            return false;
        }

        $httpRequest = $request->getHttpRequest();
        if ($httpRequest->hasHeader('X-Flow-Csrftoken')) {
            $csrfToken = $httpRequest->getHeaderLine('X-Flow-Csrftoken');
        } else {
            $internalArguments = $request->getMainRequest()->getInternalArguments();
            $csrfToken = $internalArguments['__csrfToken'] ?? null;
        }

        if (empty($csrfToken)) {
            $this->logger->debug(sprintf('CSRF: token was empty but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName));
            return true;
        }

        if (!$this->securityContext->hasCsrfProtectionTokens()) {
            throw new AuthenticationRequiredException(sprintf('CSRF: No CSRF tokens in security context, possible session timeout. A valid token is required for %s::%s()', $controllerClassName, $actionMethodName), 1317309673);
        }

        if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === false) {
            $this->logger->debug(sprintf('CSRF: token was invalid but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName));
            return true;
        }

        $this->logger->debug(sprintf('CSRF: Successfully verified token for %s::%s()', $controllerClassName, $actionMethodName));
        return false;
    }

    /**
     * @param string $className
     * @param string $methodName
     * @return boolean
     */
    protected function hasPolicyEntryForMethod($className, $methodName)
    {
        $methodPrivileges = $this->policyService->getAllPrivilegesByType(MethodPrivilegeInterface::class);
        /** @var MethodPrivilegeInterface $privilege */
        foreach ($methodPrivileges as $privilege) {
            if ($privilege->matchesMethod($className, $methodName)) {
                return true;
            }
        }
        return false;
    }
}
