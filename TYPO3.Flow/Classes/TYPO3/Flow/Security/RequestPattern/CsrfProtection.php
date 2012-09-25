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

/**
 * This class holds a request pattern that decides, if csrf protection was enabled for the current request and searches
 * for invalid csrf protection tokens.
 *
 */
class CsrfProtection implements \TYPO3\Flow\Security\RequestPatternInterface {

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 * @Flow\Inject
	 */
	protected $authenticationManager;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 * @Flow\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 * @Flow\Inject
	 */
	protected $policyService;

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
	 * Matches a \TYPO3\Flow\Mvc\RequestInterface against the configured CSRF pattern rules and searches for invalid
	 * csrf tokens.
	 *
	 * @param \TYPO3\Flow\Mvc\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	public function matchRequest(\TYPO3\Flow\Mvc\RequestInterface $request) {
		if ($this->authenticationManager->isAuthenticated() === FALSE) {
			return FALSE;
		}

		$controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
		$actionName = $request->getControllerActionName(). 'Action';

		if ($this->policyService->hasPolicyEntryForMethod($controllerClassName, $actionName) && !$this->reflectionService->isMethodTaggedWith($controllerClassName, $actionName, 'skipcsrfprotection')) {
			$internalArguments = $request->getMainRequest()->getInternalArguments();
			if (!isset($internalArguments['__csrfToken'])) {
				return TRUE;
			}
			$csrfToken = $internalArguments['__csrfToken'];
			if (!$this->securityContext->hasCsrfProtectionTokens()) {
				throw new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('No tokens in security context, possible session timeout', 1317309673);
			}
			if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === FALSE) {
				return TRUE;
			}
		}
		return FALSE;
	}
}

?>
