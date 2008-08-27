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
 *
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_AccessDecisionVoterManager implements F3_FLOW3_Security_Authorization_AccessDecisionManagerInterface {

//TODO: This has to be filled by configuration and is extended by automatic resolving jointpoint voters in the decide method
	/**
	 * @var array Array of F3_FLOW3_Security_Authorization_AccessDecisionVoterInterface objects
	 */
	protected $accessDecisionVoters = array();

//TODO: Set by configuratin
	/**
	 * @var boolean If set to TRUE access will be granted for objects where all voters abstain from decision.
	 */
	protected $allowAccessIfAllAbstain = FALSE;

	/**
	 * Decides if access should be granted on the given object in the current security context.
	 * It iterates over all available F3_FLOW3_Security_Authorization_AccessDecisionVoterInterface objects.
	 * If all voters abstain, access will be denied by default, except $allowAccessIfAllAbstain is set to TRUE.
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 */
	public function decide(F3_FLOW3_Security_Context $securityContext, F3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		//TODO: resolve voters that could vote on the given method parameters (if $object is a joinpoint)
		//return values of the voters: VOTE_GRANT, VOTE_ABSTAIN, VOTE_DENY
	}

	/**
	 * Returns TRUE if any of the configured access decision voters can decide on objects with the given classname
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision manager can decide on objects with the given classname
	 */
	public function supports($className) {

	}
}

?>