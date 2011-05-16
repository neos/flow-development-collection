<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This class holds a request pattern that decides, if csrf protection was enabled for the current request and searches
 * for invalid csrf protection tokens.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class CsrfProtection implements \F3\FLOW3\Security\RequestPatternInterface {

	/**
	 * @var \F3\FLOW3\Security\Context
	 */
	protected $securityContext;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService The reflection service
	 * @return void
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the security context
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The security context
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSecurityContext(\F3\FLOW3\Security\Context $securityContext) {
		$this->securityContext = $securityContext;
	}

	/**
	 * Injects the policy service
	 *
	 * @param \F3\FLOW3\Security\Policy\PolicyService $policyService The policy service
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectPolicyService(\F3\FLOW3\Security\Policy\PolicyService $policyService) {
		$this->policyService = $policyService;
	}

	/**
	 * Returns TRUE, if this pattern can match against the given request object.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if this pattern can match
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canMatch(\F3\FLOW3\MVC\RequestInterface $request) {
		if ($request instanceof \F3\FLOW3\MVC\Web\Request) return TRUE;
		return FALSE;
	}

	/**
	 * NULL: This pattern holds no configured pattern value
	 *
	 * @return string The set pattern (always NULL here)
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPattern() {
		return NULL;
	}

	/**
	 * Does nothing, as this pattern holds not configure pattern value
	 *
	 * @param string $uriPattern Not used
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setPattern($uriPattern) {}

	/**
	 * Matches a \F3\FLOW3\MVC\RequestInterface against the configured CSRF pattern rules and searches for invalid
	 * csrf tokens.
	 *
	 * @param \F3\FLOW3\MVC\RequestInterface $request The request that should be matched
	 * @return boolean TRUE if the pattern matched, FALSE otherwise
	 * @throws \F3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function matchRequest(\F3\FLOW3\MVC\RequestInterface $request) {
		$controllerClassName = $this->objectManager->getClassNameByObjectName($request->getControllerObjectName());
		$actionName = $request->getControllerActionName(). 'Action';

		if ($this->policyService->hasPolicyEntryForMethod($controllerClassName, $actionName)
			&& !$this->reflectionService->isMethodTaggedWith($controllerClassName, $actionName, 'skipCsrfProtection')) {
			$internalArguments = $request->getInternalArguments();
			if (!isset($internalArguments['__CSRF-TOKEN'])) return TRUE;
			$csrfToken = $internalArguments['__CSRF-TOKEN'];
			if ($this->securityContext->isCsrfProtectionTokenValid($csrfToken) === FALSE) return TRUE;
		}
		return FALSE;
	}
}

?>