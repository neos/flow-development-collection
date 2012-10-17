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

		$possibleObjectName = $this->router->getControllerObjectName($packageKey, $subpackageKey, $controllerName);
		$controllerClassName = $this->objectManager->getClassNameByObjectName($possibleObjectName);

		$lowercaseActionMethodName = ((isset($mergedArguments['@action']) && $mergedArguments['@action'] !== '') ? strtolower($mergedArguments['@action']) : 'index') . 'action';
		if ($this->shouldCsrfTokenBeAppended($controllerClassName, $lowercaseActionMethodName)) {
			$mergedArguments['__csrfToken'] = $this->securityContext->getCsrfProtectionToken();
		}

		return $mergedArguments;
	}

	/**
	 * Checks if the given controller/action pair exists and returns TRUE if that's the case and the
	 * action is not annotated with @Flow\SkipCsrfProtection.
	 * In all other cases this returns FALSE
	 *
	 * @param string $controllerClassName the fully qualified class name of the controller
	 * @param string $lowercaseActionMethodName the lower cased method name of the target action
	 * @return boolean TRUE if the CSRF token is required for the target action (it exists and is not annotated with SkipCsrfProtection)
	 */
	protected function shouldCsrfTokenBeAppended($controllerClassName, $lowercaseActionMethodName) {
		if ($controllerClassName === FALSE) {
			return FALSE;
		}
		foreach (get_class_methods($controllerClassName) as $existingMethodName) {
			if (strtolower($existingMethodName) !== $lowercaseActionMethodName) {
				continue;
			}
			if (!$this->reflectionService->hasMethod($controllerClassName, $existingMethodName)) {
				return FALSE;
			}
			return !$this->reflectionService->isMethodAnnotatedWith($controllerClassName, $existingMethodName, 'TYPO3\Flow\Annotations\SkipCsrfProtection');
		}
		return FALSE;
	}
}

?>
