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
 * A mock RequestHandlingController
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class MockRequestHandlingController extends \F3\FLOW3\MVC\Controller\RequestHandlingController {

	/**
	 * @var \F3\FLOW3\MVC\Controller\Arguments Arguments passed to the controller
	 */
	public $arguments;

	/**
	 * @var array An array of supported request types. By default all kinds of request are supported. Modify or replace this array if your specific controller only supports certain request types.
	 */
	public $supportedRequestTypes = array('F3\FLOW3\MVC\Request');

	/**
	 * @var boolean If processRequest() has been called
	 */
	public $requestHasBeenProcessed = FALSE;

	/**
	 * Doesn't really process the request but sets a flag that this method was called.
	 *
	 * @param \F3\FLOW3\MVC\Request $request
	 * @param \F3\FLOW3\MVC\Response $response
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(\F3\FLOW3\MVC\Request $request, \F3\FLOW3\MVC\Response $response) {
		parent::processRequest($request, $response);
		$this->requestHasBeenProcessed = TRUE;
	}

	/**
	 * Returns the package settings which were injected into this controller
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettings() {
		return $this->settings;
	}
}

?>