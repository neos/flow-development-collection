<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;
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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */

require_once(__DIR__ . '/../Fixture/Controller/MockRESTController.php');

/**
 * Testcase for the MVC REST Controller
 *
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RESTControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestRegistersAnIdArgument() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockArguments = $this->objectFactory->create('F3\FLOW3\MVC\Controller\Arguments');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('resolveActionMethodName', 'callActionMethod', 'initializeArguments', 'mapRequestArgumentsToLocalArguments', 'initializeView'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->processRequest($mockRequest, $mockResponse);

		$this->assertTrue(isset($mockArguments['id']));
	}
}
?>
