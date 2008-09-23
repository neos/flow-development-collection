<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization::Voter;

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
 * @version $Id$
 */

/**
 * An access decision voter, that asks the FLOW3 ACLService for a decision.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ACL implements F3::FLOW3::Security::Authorization::AccessDecisionVoterInterface {

	/**
	 * This is the default ACL voter, it votes for the ACCESS privilege
	 *
	 * @param F3::FLOW3::Security::Context $securityContext The current securit context
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	 * @throws F3::FLOW3::Security::Exception::AccessDenied If access is not granted
	 */
	public function vote(F3::FLOW3::Security::Context $securityContext, F3::FLOW3::AOP::JoinPointInterface $joinPoint) {
		//ask the current token if for the roles the user currently has
		//search for an ACCESS privilege, that isGrant(), any of the user's roles has for this joinpoint (ask the policyservice to return the privileges for each role)
	}
}

?>