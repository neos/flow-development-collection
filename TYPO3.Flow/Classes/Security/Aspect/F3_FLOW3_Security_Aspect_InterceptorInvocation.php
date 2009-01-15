<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

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
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 */

/**
 * The central security aspect, that invoces the security interceptors.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class InterceptorInvocation {

	/**
	 * The policy enforcement interceptor
	 * @var \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement
	 */
	protected $policyEnforcementInterceptor;

	/**
	 * The after invocation interceptor
	 * @var \F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation
	 */
	protected $afterInvocationInterceptor;

	/**
	 * Constructor.
	 *
	 * @param \F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement $policyEnforcementInterceptor The policy enforcement interceptor
	 * @param \F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation $afterInvocationInterceptor The after invocation interceptor
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(\F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement $policyEnforcementInterceptor, \F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation $afterInvocationInterceptor) {
		$this->policyEnforcementInterceptor = $policyEnforcementInterceptor;
		$this->afterInvocationInterceptor = $afterInvocationInterceptor;
	}

	/**
	 * The policy enforcement advice. This advices applies the security enforcement interceptor to all methods configured in the policy.
	 * Note: If we have some kind of "run as" functionality in the future, we would have to manipulate the security context
	 * before calling the policy enforcement interceptor
	 *
	 * @around filter(F3\FLOW3\Security\ACL\PolicyService)
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The current joinpoint
	 * @return The result of the target method if it has not been intercepted
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicy(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$this->policyEnforcementInterceptor->setJoinPoint($joinPoint);
		$this->afterInvocationInterceptor->setJoinPoint($joinPoint);

		$this->policyEnforcementInterceptor->invoke();

		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);

		$this->afterInvocationInterceptor->setResult($result);
		return $this->afterInvocationInterceptor->invoke();
	}
}

?>