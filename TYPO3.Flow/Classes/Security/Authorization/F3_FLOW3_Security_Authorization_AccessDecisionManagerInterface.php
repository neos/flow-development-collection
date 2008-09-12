<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization;

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
 * Contract for an access decision manager.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface AccessDecisionManagerInterface {

	/**
	 * Decides if access should be granted on the given object in the current security context
	 *
	 * @param F3::FLOW3::Security::Context $securityContext The current securit context
	 * @param F3::FLOW3::AOP::JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return boolean TRUE if access is granted, FALSE if the manager abstains from decision
	 * @throws F3::FLOW3::Security::Exception::AccessDenied If access is not granted
	 */
	public function decide(F3::FLOW3::Security::Context $securityContext, F3::FLOW3::AOP::JoinPointInterface $joinPoint);

	/**
	 * Returns TRUE if this access decision manager can decide on objects with the given classname
	 *
	 * @param string $className The classname that should be checked
	 * @return boolean TRUE if this access decision manager can decide on objects with the given classname
	 */
	public function supports($className);
}

?>