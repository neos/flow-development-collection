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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement;
use TYPO3\Flow\Security\Context;

/**
 * The central security aspect, that invokes the security interceptors.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class PolicyEnforcementAspect {

	/**
	 * The policy enforcement interceptor
	 *
	 * @var \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement
	 */
	protected $policyEnforcementInterceptor;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * @param \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement $policyEnforcementInterceptor The policy enforcement interceptor
	 * @param \TYPO3\Flow\Security\Context $securityContext
	 */
	public function __construct(PolicyEnforcement $policyEnforcementInterceptor, Context $securityContext) {
		$this->policyEnforcementInterceptor = $policyEnforcementInterceptor;
		$this->securityContext = $securityContext;
	}

	/**
	 * The policy enforcement advice. This advices applies the security enforcement interceptor to all methods configured in the policy.
	 * Note: If we have some kind of "run as" functionality in the future, we would have to manipulate the security context
	 * before calling the policy enforcement interceptor
	 *
	 * @Flow\Around("filter(TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegePointcutFilter)")
	 * @param JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function enforcePolicy(JoinPointInterface $joinPoint) {
		if ($this->securityContext->areAuthorizationChecksDisabled() !== TRUE) {
			$this->policyEnforcementInterceptor->setJoinPoint($joinPoint);
			$this->policyEnforcementInterceptor->invoke();
		}

		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

}
