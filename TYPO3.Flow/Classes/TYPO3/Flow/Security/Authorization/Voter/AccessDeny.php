<?php
namespace TYPO3\Flow\Security\Authorization\Voter;

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
 * An access decision voter, that always grants access for specific objects.
 *
 * @Flow\Scope("singleton")
 */
class AccessDeny implements \TYPO3\Flow\Security\Authorization\AccessDecisionVoterInterface {

	/**
	 * Votes to deny access, if the given object is one of the supported types
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The current security context
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer VOTE_DENY
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForJoinPoint(\TYPO3\Flow\Security\Context $securityContext, \TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {

	}

	/**
	 * Votes to deny access, if the resource exists
	 *
	 * @param \TYPO3\Flow\Security\Context $securityContext The current security context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForResource(\TYPO3\Flow\Security\Context $securityContext, $resource) {

	}
}
