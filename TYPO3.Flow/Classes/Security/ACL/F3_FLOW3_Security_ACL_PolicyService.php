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
 * The policy service reads the policy configuration. The security adivce asks this service which methods have to be intercepted by a security interceptor.
 * The access decision voters get the roles and privileges configured (in the security policy) for a specific method invocation from this service.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ACL_PolicyService implements F3_FLOW3_AOP_PointcutFilterInterface {

	/**
	 * Constructor.
	 *
	 * @param F3_FLOW3_Configuration_Manager $configurationManager The configuration manager
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct(F3_FLOW3_Configuration_Manager $configurationManager) {
		//Load the policy
	}

	/**
	 * Checks if the specified class and method matches against the filter, i.e. if there is a policy entry to intercept this method.
	 *
	 * @param F3_FLOW3_Reflection_Class $class The class to check the name of
	 * @param F3_FLOW3_Reflection_Method $method The method to check the name of
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the names match, otherwise FALSE
	 */
	public function matches(F3_FLOW3_Reflection_Class $class, F3_FLOW3_Reflection_Method $method, $pointcutQueryIdentifier) {
		//if there is a match, lay the configured roles and privileges for this method interception in a cache
	}

	/**
	 * Returns the configured roles for the given joinpoint
	 *
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @return array Array of F3_FLOW3_Security_ACL_Role objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRoles(F3_FLOW3_AOP_JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns the privileges a specific role has for the given joinpoint
	 *
	 * @param F3_FLOW3_Security_ACL_Role $role The role for which the privileges should be returned
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint The joinpoint for which the roles should be returned
	 * @param string $type Return only a special privilege e.g. ACCESS
	 * @return array Array of F3_FLOW3_Security_ACL_Privilege objects
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPrivileges(F3_FLOW3_Security_ACL_Role $role, F3_FLOW3_AOP_JoinPointInterface $joinPoint, $type = '') {
		//if the role is a composite role we walk up in the tree and the first PRIVILEGE_GRANT or PRIVILEGE_DENY found will be added to the returned privileges
	}
}

?>