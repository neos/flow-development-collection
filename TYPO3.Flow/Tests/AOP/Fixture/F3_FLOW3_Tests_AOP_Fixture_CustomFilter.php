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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * A dummy custom pointcut filter
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Tests_AOP_Fixture_CustomFilter implements F3_FLOW3_AOP_PointcutFilterInterface {

	/**
	 * Matches always
	 *
	 * @return TRUE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(F3_FLOW3_Reflection_Class $class, F3_FLOW3_Reflection_Method $method, $pointcutQueryIdentifier) {
		return TRUE;
	}

}
?>