<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 */

/**
 * An access decision voter, that asks the FLOW3 ACLService for a decision.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_Voter_ACL implements F3_FLOW3_Security_Authorization_AccessDecisionVoterInterface {

	/**
	 * This is the default ACL voter, it votes for the ACCESS privilege
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 */
	public function vote(F3_FLOW3_Security_Context $securityContext, F3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		//ask the current token if for the roles the user currently has
		//search for an ACCESS privilege, that isGrant(), any of the user's roles has for this joinpoint (ask the policyservice to return the privileges for each role)
	}

	/**
	 *
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision voter can vote for objects with the given classname
	 */
	public function supports($className) {

	}
}

?>