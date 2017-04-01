<?php
namespace TYPO3\Flow\Security\Authorization\Voter;

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

/**
 * An access decision voter, that asks the Flow PolicyService for a decision.
 *
 * @Flow\Scope("singleton")
 */
class Policy implements \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface
{
    /**
     * The policy service
     * @var \TYPO3\Flow\Security\Policy\PolicyService
     */
    protected $policyService;

    /**
     * Constructor.
     *
     * @param \TYPO3\Flow\Security\Policy\PolicyService $policyService The policy service
     */
    public function __construct(\TYPO3\Flow\Security\Policy\PolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * This is the default Policy voter, it votes for the access privilege for the given join point
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to vote for
     * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
     */
    public function voteForJoinPoint(\TYPO3\Flow\Security\Context $securityContext, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $accessGrants = 0;
        $accessDenies = 0;
        foreach ($securityContext->getRoles() as $role) {
            try {
                $privileges = $this->policyService->getPrivilegesForJoinPoint($role, $joinPoint);
            } catch (\TYPO3\Flow\Security\Exception\NoEntryInPolicyException $e) {
                return self::VOTE_ABSTAIN;
            }

            foreach ($privileges as $privilege) {
                if ($privilege === \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT) {
                    $accessGrants++;
                } elseif ($privilege === \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY) {
                    $accessDenies++;
                }
            }
        }

        if ($accessDenies > 0) {
            return self::VOTE_DENY;
        }
        if ($accessGrants > 0) {
            return self::VOTE_GRANT;
        }

        return self::VOTE_ABSTAIN;
    }

    /**
     * This is the default Policy voter, it votes for the access privilege for the given resource
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param string $resource The resource to vote for
     * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
     */
    public function voteForResource(\TYPO3\Flow\Security\Context $securityContext, $resource)
    {
        $accessGrants = 0;
        $accessDenies = 0;
        foreach ($securityContext->getRoles() as $role) {
            try {
                $privilege = $this->policyService->getPrivilegeForResource($role, $resource);
            } catch (\TYPO3\Flow\Security\Exception\NoEntryInPolicyException $e) {
                return self::VOTE_ABSTAIN;
            }

            if ($privilege === null) {
                continue;
            }

            if ($privilege === \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_GRANT) {
                $accessGrants++;
            } elseif ($privilege === \TYPO3\Flow\Security\Policy\PolicyService::PRIVILEGE_DENY) {
                $accessDenies++;
            }
        }

        if ($accessDenies > 0) {
            return self::VOTE_DENY;
        }
        if ($accessGrants > 0) {
            return self::VOTE_GRANT;
        }

        return self::VOTE_ABSTAIN;
    }
}
