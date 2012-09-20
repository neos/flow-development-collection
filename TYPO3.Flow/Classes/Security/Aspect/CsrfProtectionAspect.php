<?php
namespace TYPO3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect which cares for CSRF protection.
 *
 * @FLOW3\Aspect
 */
class CsrfProtectionAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Adds a CSRF token as argument in the URI builder
	 *
	 * @FLOW3\Around("setting(TYPO3.FLOW3.security.enable) && method(TYPO3\FLOW3\Mvc\Routing\UriBuilder->mergeArgumentsWithRequestArguments())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array
	 */
	public function addCsrfTokenToUri(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$mergedArguments = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->authenticationManager->isAuthenticated() === FALSE || $joinPoint->getProxy()->isLinkProtectionEnabled() === FALSE) {
			return $mergedArguments;
		}

		$packageKey = (isset($mergedArguments['@package']) ? $mergedArguments['@package'] : '');
		$subpackageKey = (isset($mergedArguments['@subpackage']) ? $mergedArguments['@subpackage'] : '');
		$controllerName = (isset($mergedArguments['@controller']) ? $mergedArguments['@controller'] : 'Standard');
		$actionName = ((isset($mergedArguments['@action']) && $mergedArguments['@action'] !== '') ? $mergedArguments['@action'] : 'index') . 'Action';

		$possibleObjectName = $this->router->getControllerObjectName($packageKey, $subpackageKey, $controllerName);
		$className = $this->objectManager->getClassNameByObjectName($possibleObjectName);

		if ($className !== FALSE && $this->reflectionService->hasMethod($className, $actionName)) {
			if (!$this->reflectionService->isMethodAnnotatedWith($className, $actionName, 'TYPO3\FLOW3\Annotations\SkipCsrfProtection')) {
				$mergedArguments['__csrfToken'] = $this->securityContext->getCsrfProtectionToken();
			}
		}
		return $mergedArguments;
	}
}

?>
