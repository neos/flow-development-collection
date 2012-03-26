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
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @FLOW3\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 * @FLOW3\Inject
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Security\Context
	 * @FLOW3\Inject
	 */
	protected $securityContext;

	/**
	 * @var \TYPO3\FLOW3\Security\Policy\PolicyService
	 * @FLOW3\Inject
	 */
	protected $policyService;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 * @FLOW3\Inject
	 */
	protected $environment;

	/**
	 * Adds a CSRF token as argument in the URI builder
	 *
	 * @FLOW3\Before("setting(TYPO3.FLOW3.security.enable) && method(TYPO3\FLOW3\Mvc\Routing\UriBuilder->build())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function addCsrfTokenToUri(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$uriBuilder = $joinPoint->getProxy();
		$arguments = $joinPoint->getMethodArgument('arguments');
		$packageKey = (isset($arguments['@package']) ? $arguments['@package'] : '');
		$subpackageKey = (isset($arguments['@subpackage']) ? $arguments['@subpackage'] : '');
		$controllerName = (isset($arguments['@controller']) ? $arguments['@controller'] : 'Standard');
		$actionName = (isset($arguments['@action']) ? $arguments['@action'] : 'index') . 'Action';

		$possibleObjectName = '@package\@subpackage\Controller\@controllerController';
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $subpackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);
		$lowercaseObjectName = strtolower($possibleObjectName);

		$className = $this->objectManager->getClassNameByObjectName($this->objectManager->getCaseSensitiveObjectName($lowercaseObjectName));
		if ($this->policyService->hasPolicyEntryForMethod($className, $actionName)
			&& !$this->reflectionService->isMethodAnnotatedWith($className, $actionName, 'TYPO3\FLOW3\Annotations\SkipCsrfProtection')) {
			$internalArguments = $uriBuilder->getArguments();
			$internalArguments['__csrfToken'] = $this->securityContext->getCsrfProtectionToken();
			$uriBuilder->setArguments($internalArguments);
		}
	}

	/**
	 * Adds a CSRF token as argument in ExtDirect requests
	 *
	 * @FLOW3\Around("method(TYPO3\ExtJS\ExtDirect\Transaction->buildRequest()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function transferCsrfTokenToExtDirectRequests(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$arguments = $this->environment->getRequestUri()->getArguments();
		$request = $joinPoint->getAdviceChain()->proceed($joinPoint);

		if (isset($arguments['__csrfToken'])) {
			$requestArguments = $request->getArguments();
			$requestArguments['__csrfToken'] = $arguments['__csrfToken'];
			$request->setArguments($requestArguments);
		}

		return $request;
	}
}

?>
