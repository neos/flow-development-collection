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

require_once(__DIR__ . '/../Fixture/Controller/MockRESTController.php');

/**
 * Testcase for the MVC REST Controller
 *
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
		$mockUriBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriBuilder');

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\Routing\UriBuilder')->will($this->returnValue($mockUriBuilder));

		$mockArguments = $this->objectFactory->create('F3\FLOW3\MVC\Controller\Arguments');

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('resolveActionMethodName', 'callActionMethod', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'resolveView', 'buildControllerContext'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('objectFactory', $mockObjectFactory);
		$controller->processRequest($mockRequest, $mockResponse);

		$this->assertTrue(isset($mockArguments['id']));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveActionMethodNameOnlyResolvesRESTMethodNamesIfTheActionNameIsIndex() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->exactly(2))->method('getControllerActionName')->will($this->returnValue('foo'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('fooAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');

		$this->assertSame('fooAction', $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForGETRequestsWithoutIdIsList() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(FALSE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('list');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('list'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('listAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForGETRequestsWithIdIsShow() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('show');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('show'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('showAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForPOSTRequestsIsCreate() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('create');

		$mockRequest->expects($this->at(3))->method('getControllerActionName')->will($this->returnValue('create'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('createAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForPUTRequestsIsUpdate() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('update');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('update'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('updateAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPUTRequestWithoutIdWillThrowAStatus400() {
		$throwStopException = function() { throw new \F3\FLOW3\MVC\Exception\StopAction(); };

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(FALSE));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForDELETERequestsIsDelete() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('delete'));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('deleteAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aDELETERequestWithoutIdWillThrowAStatus400() {
		$throwStopException = function() { throw new \F3\FLOW3\MVC\Exception\StopAction(); };

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('id')->will($this->returnValue(FALSE));

		$controller = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\RESTController'), array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$result = $controller->_call('resolveActionMethodName');
	}

}
?>
