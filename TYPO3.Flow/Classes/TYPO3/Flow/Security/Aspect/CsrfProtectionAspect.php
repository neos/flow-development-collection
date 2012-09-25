<?php
namespace TYPO3\Flow\Security\Aspect;

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
 * An aspect which cares for CSRF protection.
 *
 * @Flow\Aspect
 */
class CsrfProtectionAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface
	 */
	protected $authenticationManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * Adds a CSRF token as argument in the URI builder
	 *
	 * @Flow\Around("setting(TYPO3.Flow.security.enable) && method(TYPO3\Flow\Mvc\Routing\UriBuilder->mergeArgumentsWithRequestArguments())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array
	 */
	public function addCsrfTokenToUri(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
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
			if (!$this->reflectionService->isMethodAnnotatedWith($className, $actionName, 'TYPO3\Flow\Annotations\SkipCsrfProtection')) {
				$mergedArguments['__csrfToken'] = $this->securityContext->getCsrfProtectionToken();
			}
		}
		return $mergedArguments;
	}
}

?>
