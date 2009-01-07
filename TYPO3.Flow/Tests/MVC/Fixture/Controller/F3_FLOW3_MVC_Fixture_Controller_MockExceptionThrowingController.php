<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Fixture\Controller;

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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A mock action controller which throws a "stop action exception"
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class MockExceptionThrowingController extends \F3\FLOW3\MVC\Controller\ActionController {

	protected $initializeView = FALSE;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stopActionAction() {
		throw new \F3\FLOW3\MVC\Exception\StopAction();
	}

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwGeneralExceptionAction() {
		throw new \F3\FLOW3\MVC\Exception();
	}
}

?>