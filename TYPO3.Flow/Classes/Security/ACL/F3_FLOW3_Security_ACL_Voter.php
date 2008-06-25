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
class F3_FLOW3_Security_ACL_Voter implements F3_FLOW3_Security_Authorization_AccessDecisionVoterInterface {

	/**
	 * This is the default ACL voter. Note: The whole ACL package is based on AOP.
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param object $joinPoint The join point (method invocation) to vote for, must be a F3_FLOW3_AOP_JoinPointInterface object
	 * @return integer One of: ACCESS_GRANTED, ACCESS_ABSTAIN, ACCESS_DENIED
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 */
	public function vote(F3_FLOW3_Security_Context $securityContext, object $joinPoint) {
		//Throw exception if $joinPoint is not a join point
		//search the acl tree for rules for this method invocation
		//ask the current token if for the rules the user currently has and compare to the ones in the acl tree
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