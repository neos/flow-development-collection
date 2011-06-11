<?php
namespace F3\FLOW3\Security\Authorization\Interceptor;

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
 * This security interceptor always denys access.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 */
class AccessDeny implements \F3\FLOW3\Security\Authorization\InterceptorInterface {

	/**
	 * Invokes nothing, always throws an AccessDenied Exception.
	 *
	 * @return boolean Always returns FALSE
	 * @throws \F3\FLOW3\Security\Exception\AccessDeniedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {
		throw new \F3\FLOW3\Security\Exception\AccessDeniedException('You are not allowed to perform this action.', 1216919280);
	}
}

?>