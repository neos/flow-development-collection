<?php
namespace TYPO3\FLOW3\Security\Authorization\Voter;

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
 * An access decision voter, that always grants access for specific objects.
 *
 * @FLOW3\Scope("singleton")
 */
class AccessGrant implements \TYPO3\FLOW3\Security\Authorization\AccessDecisionVoterInterface {

	/**
	 * Votes to grant access, if the given object is one of the supported types
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current securit context
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForJoinPoint(\TYPO3\FLOW3\Security\Context $securityContext, \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {

	}

	/**
	 * Votes to grant access, if the resource exists
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current securit context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForResource(\TYPO3\FLOW3\Security\Context $securityContext, $resource) {

	}
}

?>