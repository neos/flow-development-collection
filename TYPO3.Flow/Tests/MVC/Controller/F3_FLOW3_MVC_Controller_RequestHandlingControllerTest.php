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
 * @subpackage MVC
 * @version $Id$
 */

require_once(__DIR__ . '/../Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController.php');

/**
 * Testcase for the MVC Request Handling Controller
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class RequestHandlingControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlySupportedRequestTypesAreAccepted() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');
		$controller = new \F3\FLOW3\MVC\Fixture\Controller\MockRequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->supportedRequestTypes = array('F3\Something\Request');

		try {
			$controller->processRequest($request, $response);
			$this->fail('The request handling controller accepted an unsupported request type.');
		} catch (\F3\FLOW3\MVC\Exception\UnsupportedRequestType $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentsReturnsAnArgumentsObject() {
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array('create'));
		$mockObjectFactory->expects($this->once())->method('create')->will($this->returnValue($mockArguments));
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\ManagerInterface');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($mockObjectFactory, $mockPackageManager);
		$this->assertType('F3\FLOW3\MVC\Controller\Arguments', $controller->getArguments(), 'getArguments() did not return an arguments object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequest() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$this->assertFalse($request->isDispatched());
		$controller->processRequest($request, $response);
		$this->assertTrue($request->isDispatched());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardThrowsAStopActionException() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$controller->processRequest($request, $response);
		$controller->forward('index');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardResetsTheDispatchedFlagOfTheRequest() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$controller->processRequest($request, $response);
		$this->assertTrue($request->isDispatched());
		try {
			$controller->forward('index');
		} catch(\F3\FLOW3\MVC\Exception\StopAction $exception) {
		}
		$this->assertFalse($request->isDispatched());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSetsTheSpecifiedControllerActionAndArgumentsInToTheRequest() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$controller->processRequest($request, $response);
		try {
			$controller->forward('some', 'Alternative', 'TestPackage');
		} catch(\F3\FLOW3\MVC\Exception\StopAction $exception) {
		}

		$this->assertEquals('some', $request->getControllerActionName());
		$this->assertEquals('Alternative', $request->getControllerName());
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function redirectThrowsAStopActionException() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$controller->processRequest($request, $response);
		$controller->redirect('http://typo3.org');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$request = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Request');
		$response = $this->objectManager->getObject('F3\FLOW3\MVC\Web\Response');

		$controller = new \F3\FLOW3\MVC\Controller\RequestHandlingController($this->objectFactory, $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface'));
		$controller->injectPropertyMapper($this->objectManager->getObject('F3\FLOW3\Property\Mapper'));

		$controller->processRequest($request, $response);
		try {
			$controller->throwStatus(404, 'File Really Not Found', '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');
			$this->fail('The exception was not thrown.');
		} catch (\F3\FLOW3\MVC\Exception\StopAction $exception) {
		}

		$expectedHeaders = array(
			'HTTP/1.1 404 File Really Not Found',
		);
		$this->assertEquals($expectedHeaders, $response->getHeaders());
		$this->assertEquals('<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>', $response->getContent());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function thePropertyMapperIsConfiguredWithTheCorrectArgumentFilters() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function thePropertyMapperIsConfiguredWithTheCorrectArgumentPropertyConverters() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function thePropertyMapperIsConfiguredWithTheArgumentsValidator() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function thePropertyMapperIsConfiguredWithTheArgumentsObjectAsTarget() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theRawArgumentsAreMappedByThePropertyMapper() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function everyArgumentThatRaisedAnErrorInTheMappingProcessIsMarkedInvalid() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function errorsAndWarningsAreAddedToTheCorrespondigArgumentObjects() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function forUnregisteredArgumentsAWarningIsAdded() {
		$this->markTestIncomplete();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function requiredArgumentsAreConfiguredAsRequiredPropertiesInThePropertyMapper() {
		$this->markTestIncomplete();
	}
}
?>