<?php
namespace TYPO3\FLOW3\Security\Authorization;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for an access decision voter.
 *
 */
interface AccessDecisionVoterInterface {

	const
		VOTE_GRANT = 1,
		VOTE_ABSTAIN = 2,
		VOTE_DENY = 3;

	/**
	 * Votes if access should be granted for the given object in the current security context
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current security context
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The joinpoint to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForJoinPoint(\TYPO3\FLOW3\Security\Context $securityContext, \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint);

	/**
	 * Votes if access should be granted for the given resource in the current security context
	 *
	 * @param \TYPO3\FLOW3\Security\Context $securityContext The current security context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws \TYPO3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForResource(\TYPO3\FLOW3\Security\Context $securityContext, $resource);
}

?>