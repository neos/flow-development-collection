<?php
namespace TYPO3\Flow\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Exception\AuthenticationRequiredException;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\RequestPatternInterface;

/**
 * This class holds a request pattern that decides, if csrf protection was enabled for the current request and searches
 * for invalid csrf protection tokens.
 */
class CsrfProtection implements RequestPatternInterface {

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
	 * NULL: This pattern holds no configured pattern value
	 *
	 * @return string The set pattern (always NULL here)
	 */
	public function getPattern() {}

	/**
	 * Does nothing, as this pattern holds no configured pattern value
	 *
	 * @param string $pattern Not used
	 * @return void
	 */
	public function setPattern($pattern) {}

	/**
	 * Matches a \TYPO3\Flow\Mvc\RequestInterface against the configured CSRF pattern rules and
	 * searches for invalid csrf tokens. If this returns TRUE, the request is invalid!
	 *
	 * @param RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws AuthenticationRequiredException
	 */
	public function matchRequest(RequestInterface $request) {
		if (!$request instanceof ActionRequest || $request->getHttpRequest()->isMethodSafe()) {
			$this->systemLogger->log('CSRF: No token required, safe request', LOG_DEBUG);
			return FALSE;
		}
		if ($this->authenticationManager->isAuthenticated() === FALSE) {
			$this->systemLogger->log('CSRF: No token required, not authenticated', LOG_DEBUG);
			return FALSE;
		}
		if ($this->securityContext->areAuthorizationChecksDisabled() === TRUE) {
			$this->systemLogger->log('CSRF: No token required, authorization checks are disabled', LOG_DEBUG);
			return FALSE;
		}

		$controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
		$actionMethodName = $request->getControllerActionName() . 'Action';

		if (!$this->hasPolicyEntryForMethod($controllerClassName, $actionMethodName)) {
			$this->systemLogger->log(sprintf('CSRF: No token required, method %s::%s() is not restricted by a policy.', $controllerClassName, $actionMethodName), LOG_DEBUG);
			return FALSE;
		}
		if ($this->reflectionService->isMethodTaggedWith($controllerClassName, $actionMethodName, 'skipcsrfprotection')) {
			$this->systemLogger->log(sprintf('CSRF: No token required, method %s::%s() is tagged with a "skipcsrfprotection" annotation', $controllerClassName, $actionMethodName), LOG_DEBUG);
			return FALSE;
		}

		$httpRequest = $request->getHttpRequest();
		if ($httpRequest->hasHeader('X-Flow-Csrftoken')) {
			$csrfToken = $httpRequest->getHeader('X-Flow-Csrftoken');
		} else {
			$internalArguments = $request->getMainRequest()->getInternalArguments();
			$csrfToken = isset($internalArguments['__csrfToken']) ? $internalArguments['__csrfToken'] : NULL;
		}

		if (empty($csrfToken)) {
			$this->systemLogger->log(sprintf('CSRF: token was empty but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName), LOG_DEBUG);
			return TRUE;
		}

		if (!$this->securityContext->hasCsrfProtectionTokens()) {
			throw new AuthenticationRequiredException(sprintf('CSRF: No CSRF tokens in security context, possible session timeout. A valid token is required for %s::%s()', $controllerClassName, $actionMethodName), 1317309673);
		}

		if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === FALSE) {
			$this->systemLogger->log(sprintf('CSRF: token was invalid but a valid token is required for %s::%s()', $controllerClassName, $actionMethodName), LOG_DEBUG);
			return TRUE;
		}

		$this->systemLogger->log(sprintf('CSRF: Successfully verified token for %s::%s()', $controllerClassName, $actionMethodName),  LOG_DEBUG);
		return FALSE;
	}

	/**
	 * @param string $className
	 * @param string $methodName
	 * @return boolean
	 */
	protected function hasPolicyEntryForMethod($className, $methodName) {
		$methodPrivileges = $this->policyService->getAllPrivilegesByType('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface');
		/** @var MethodPrivilegeInterface $privilege */
		foreach ($methodPrivileges as $privilege) {
			if ($privilege->matchesMethod($className, $methodName)) {
				return TRUE;
			}
		}
		return FALSE;
	}
}