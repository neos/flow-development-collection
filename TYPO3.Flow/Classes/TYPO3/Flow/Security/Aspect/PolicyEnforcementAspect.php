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
	 * The after invocation interceptor
	 * @var \TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation
	 */
	protected $afterInvocationInterceptor;

	/**
	 * Constructor.
	 *
	 * @param \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement $policyEnforcementInterceptor The policy enforcement interceptor
	 */
	public function __construct(\TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement $policyEnforcementInterceptor) {
		$this->policyEnforcementInterceptor = $policyEnforcementInterceptor;
	}

	/**
	 * The policy enforcement advice. This advices applies the security enforcement interceptor to all methods configured in the policy.
	 * Note: If we have some kind of "run as" functionality in the future, we would have to manipulate the security context
	 * before calling the policy enforcement interceptor
	 *
	 * @Flow\Around("setting(TYPO3.Flow.security.enable) && filter(TYPO3\Flow\Security\Policy\PolicyService)")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current joinpoint
	 * @return mixed The result of the target method if it has not been intercepted
	 */
	public function enforcePolicy(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$this->policyEnforcementInterceptor->setJoinPoint($joinPoint);
		$this->policyEnforcementInterceptor->invoke();

		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);

			// @TODO Once we use the AfterInvocation again, it needs to be invoked here and its result returned instead.
		return $result;
	}

	/**
	 * Makes sure that the AOP proxies are rebuilt if a policy has changed.
	 *
	 * Note: This is not an advice but a method which is used as a slot for a signal
	 *       sent by the system file monitor defined in the bootstrap scripts.
	 *
	 * @param string $fileMonitorIdentifier Identifier of the File Monitor
	 * @param array $changedFiles A list of full paths to changed files
	 * @return void
	 */
	public function triggerAopProxyRebuildingByChangedFiles($fileMonitorIdentifier, array $changedFiles) {
		if ($fileMonitorIdentifier !== 'Flow_PolicyFiles') {
			return;
		}
		$this->systemLogger->log('The security policies have changed, thus triggering an AOP proxy class rebuild.', LOG_INFO);

		$this->flushCachesByTag(\TYPO3\Flow\Cache\CacheManager::getClassTag());
		$this->flushCachesByTag(\TYPO3\Flow\Cache\CacheManager::getClassTag('TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect'));
	}

}

?>