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
 * An access decision voter, that always grants access for specific objects.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authorization_Voter_AccessGrant implements F3_FLOW3_Security_Authorization_AccessDecisionVoterInterface {

	/**
	 * @var array Array of classnames this voter should support
	 * @todo This has to be set by configuration
	 */
	protected $supportedClasses = array();

	/**
	 * Votes to grant access, if the given object is one of the supported types
	 *
	 * @param F3_FLOW3_Security_Context $securityContext The current securit context
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return integer One of: VOTE_GRANT
	 * @throws F3_FLOW3_Security_Exception_AccessDenied If access is not granted
	 */
	public function vote(F3_FLOW3_Security_Context $securityContext, F3_FLOW3_AOP_JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns TRUE if the given classname is contained in $this->supportedClasses
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision voter can vote for objects with the given classname
	 */
	public function supports($className) {

	}
}

?>