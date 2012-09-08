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
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Core\Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var \TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface
	 * @FLOW3\Inject
	 */
	protected $authenticationManager;

	/**
	 * Adds a CSRF token as argument in the URI builder
	 *
	 * @FLOW3\Around("setting(TYPO3.FLOW3.security.enable) && method(TYPO3\FLOW3\Mvc\Routing\UriBuilder->addNamespaceToArguments())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array
	 */
	public function addCsrfTokenToUri(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$arguments = $joinPoint->getMethodArgument('arguments');
		$namespacedArguments = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($this->authenticationManager->isAuthenticated() === FALSE) {
			return $namespacedArguments;
		}

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
		if (!$this->reflectionService->isMethodAnnotatedWith($className, $actionName, 'TYPO3\FLOW3\Annotations\SkipCsrfProtection')) {
			$namespacedArguments['__csrfToken'] = $this->securityContext->getCsrfProtectionToken();
		}

		return $namespacedArguments;
	}

	/**
	 * Adds a CSRF token as argument in ExtDirect requests
	 *
	 * @FLOW3\Around("method(TYPO3\ExtJS\ExtDirect\Transaction->buildRequest()) && setting(TYPO3.FLOW3.security.enable)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	public function transferCsrfTokenToExtDirectRequests(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$extDirectRequest = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$requestHandler = $this->bootstrap->getActiveRequestHandler();
		if ($requestHandler instanceof \TYPO3\FLOW3\Http\HttpRequestHandlerInterface) {
			$arguments = $requestHandler->getHttpRequest()->getArguments();
			if (isset($arguments['__csrfToken'])) {
				$requestArguments = $extDirectRequest->getMainRequest()->getArguments();
				$requestArguments['__csrfToken'] = $arguments['__csrfToken'];
				$extDirectRequest->getMainRequest()->setArguments($requestArguments);
			}
		}

		return $extDirectRequest;
	}
}

?>
