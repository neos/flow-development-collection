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
	 * @var \F3\FLOW3\MVC\Controller\RESTController
	 */
	protected $mockController;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockController = new \F3\FLOW3\MVC\Fixture\Controller\MockRESTController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$this->mockController->injectObjectManager($this->objectManager);
		$this->mockController->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionCallsTheListActionOnGETRequestsWithoutIdentifier() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$this->mockController->processRequest($request, $response);
		$this->assertEquals('list action called', $response->getContent());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionCallsTheShowActionOnGETRequestsWithIdentifier() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$request->setArgument('id', '6499348f-f8fd-48de-9979-24e1edc2fbe7');

		$this->mockController->processRequest($request, $response);
		$this->assertEquals('show action called', $response->getContent());
	}
}
?>