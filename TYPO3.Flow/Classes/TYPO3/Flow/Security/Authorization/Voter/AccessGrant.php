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
 * An access decision voter, that always grants access for specific objects.
 *
 * @Flow\Scope("singleton")
 */
class AccessGrant implements \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface
{
    /**
     * Votes to grant access, if the given object is one of the supported types
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
     * @return integer One of: VOTE_GRANT
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function voteForJoinPoint(\TYPO3\Flow\Security\Context $securityContext, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
    }

    /**
     * Votes to grant access, if the resource exists
     *
     * @param \TYPO3\Flow\Security\Context $securityContext The current security context
     * @param string $resource The resource to vote for
     * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
     * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
     */
    public function voteForResource(\TYPO3\Flow\Security\Context $securityContext, $resource)
    {
    }
}
