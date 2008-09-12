<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization::Interceptor;

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
 * This security interceptor always denys access.
 *
 * @package FLOW3
 * @subpackage Security
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AccessDeny implements F3::FLOW3::Security::Authorization::InterceptorInterface {

	/**
	 * Invokes nothing, always throws an AccessDenied Exception.
	 *
	 * @return boolean Always returns FALSE
	 * @throws F3::FLOW3::Security::Exception::AccessDenied
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke() {
		throw new F3::FLOW3::Security::Exception::AccessDenied('You are not allowed to perform this action.', 1216919280);

		return FALSE;
	}
}

?>