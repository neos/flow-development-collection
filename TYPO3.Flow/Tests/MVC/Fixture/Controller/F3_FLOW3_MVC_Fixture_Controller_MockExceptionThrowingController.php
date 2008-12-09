<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Fixture::Controller;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * A mock action controller which throws a "stop action exception"
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MockExceptionThrowingController extends F3::FLOW3::MVC::Controller::ActionController {

	protected $initializeView = FALSE;

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stopActionAction() {
		throw new F3::FLOW3::MVC::Exception::StopAction();
	}

	/**
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwGeneralExceptionAction() {
		throw new F3::FLOW3::MVC::Exception();
	}
}

?>