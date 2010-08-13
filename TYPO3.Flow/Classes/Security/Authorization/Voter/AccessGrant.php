<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Voter;

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
 * An access decision voter, that always grants access for specific objects.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AccessGrant implements \F3\FLOW3\Security\Authorization\AccessDecisionVoterInterface {

	/**
	 * Votes to grant access, if the given object is one of the supported types
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The current securit context
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT
	 * @throws \F3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForJoinPoint(\F3\FLOW3\Security\Context $securityContext, \F3\FLOW3\AOP\JoinPointInterface $joinPoint) {

	}

	/**
	 * Votes to grant access, if the resource exists
	 *
	 * @param \F3\FLOW3\Security\Context $securityContext The current securit context
	 * @param string $resource The resource to vote for
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws \F3\FLOW3\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function voteForResource(\F3\FLOW3\Security\Context $securityContext, $resource) {

	}
}

?>