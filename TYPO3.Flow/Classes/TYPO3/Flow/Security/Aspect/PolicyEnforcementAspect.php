<?php
namespace TYPO3\Flow\Security\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
class PolicyEnforcementAspect
{
    /**
     * The policy enforcement interceptor
     *
     * @var PolicyEnforcement
     */
    protected $policyEnforcementInterceptor;

    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * @param PolicyEnforcement $policyEnforcementInterceptor The policy enforcement interceptor
     * @param Context $securityContext
     */
    public function __construct(PolicyEnforcement $policyEnforcementInterceptor, Context $securityContext)
    {
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
    public function enforcePolicy(JoinPointInterface $joinPoint)
    {
        if ($this->securityContext->areAuthorizationChecksDisabled() !== true) {
            $this->policyEnforcementInterceptor->setJoinPoint($joinPoint);
            $this->policyEnforcementInterceptor->invoke();
        }

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }
}
