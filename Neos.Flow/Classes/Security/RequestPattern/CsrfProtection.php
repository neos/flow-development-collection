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
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\RequestPatternInterface;

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
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * Matches a \Neos\Flow\Mvc\RequestInterface against the configured CSRF pattern rules and
     * searches for invalid csrf tokens. If this returns TRUE, the request is invalid!
     *
     * @param RequestInterface $request The request that should be matched
     * @return boolean TRUE if the pattern matched, FALSE otherwise
     * @throws AuthenticationRequiredException
     */
    public function matchRequest(RequestInterface $request)
    {
        if (!$request instanceof ActionRequest || $request->getHttpRequest()->isMethodSafe()) {
            $this->systemLogger->log('CSRF: No token required, safe request', LOG_DEBUG);
            return false;
        }
        if ($this->authenticationManager->isAuthenticated() === false) {
            $this->systemLogger->log('CSRF: No token required, not authenticated', LOG_DEBUG);
            return false;
        }
        if ($this->securityContext->areAuthorizationChecksDisabled() === true) {
            $this->systemLogger->log('CSRF: No token required, authorization checks are disabled', LOG_DEBUG);
            return false;
        }

        $controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
        $actionMethodName = $request->getControllerActionName() . 'Action';

        if (!$this->hasPolicyEntryForMethod($controllerClassName, $actionMethodName)) {
            $this->systemLogger->log(sprintf('CSRF: No token required, method %s::%s() is not restricted by a policy.', $controllerClassName, $actionMethodName), LOG_DEBUG);
            return false;
        }
        if ($this->reflectionService->isMethodTaggedWith($controllerClassName, $actionMethodName, 'skipcsrfprotection')) {
            $this->systemLogger->log(sprintf('CSRF: No token required, method %s::%s() is tagged with a "skipcsrfprotection" annotation', $controllerClassName, $actionMethodName), LOG_DEBUG);
            return false;
        }

        $httpRequest = $request->getHttpRequest();
        if ($httpRequest->hasHeader('X-Flow-Csrftoken')) {
            $csrfToken = $httpRequest->getHeader('X-Flow-Csrftoken');
        } else {
            $internalArguments = $request->getMainRequest()->getInternalArguments();
            $csrfToken = isset($internalArguments['__csrfToken']) ? $internalArguments['__csrfToken'] : null;
        }

        if (empty($csrfToken)) {
            $this->systemLogger->log(sprintf('CSRF: token was empty but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName), LOG_DEBUG);
            return true;
        }

        if (!$this->securityContext->hasCsrfProtectionTokens()) {
            throw new AuthenticationRequiredException(sprintf('CSRF: No CSRF tokens in security context, possible session timeout. A valid token is required for %s::%s()', $controllerClassName, $actionMethodName), 1317309673);
        }

        if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === false) {
            $this->systemLogger->log(sprintf('CSRF: token was invalid but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName), LOG_DEBUG);
            return true;
        }

        $this->systemLogger->log(sprintf('CSRF: Successfully verified token for %s::%s()', $controllerClassName, $actionMethodName), LOG_DEBUG);
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
