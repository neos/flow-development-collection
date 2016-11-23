<?php
namespace TYPO3\Flow\Security\Authorization;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Contract for an access decision voter.
 *
 */
interface AccessDecisionVoterInterface
{
    const VOTE_GRANT = 1;
    const VOTE_ABSTAIN = 2;
    const VOTE_DENY = 3;

    /**
     * Votes if access should be granted for the given object in the current security context
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to vote for
     * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function voteForJoinPoint(\TYPO3\Flow\Security\Context $securityContext, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint);

    /**
     * Votes if access should be granted for the given resource in the current security context
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param string $resource The resource to vote for
     * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function voteForResource(\TYPO3\Flow\Security\Context $securityContext, $resource);
}
