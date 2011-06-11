<?php
namespace F3\FLOW3\Tests\Unit\MVC\Controller;
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
 * Testcase for the MVC REST Controller
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RestControllerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveActionMethodNameOnlyResolvesRESTMethodNamesIfTheActionNameIsIndex() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->exactly(2))->method('getControllerActionName')->will($this->returnValue('foo'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('fooAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');

		$this->assertSame('fooAction', $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForGETRequestsWithoutProvidedResourceIsList() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('list');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('list'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('listAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function actionNameForGETRequestsWithProvidedResourceIsShow() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('show');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('show'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('showAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
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

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('createAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
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
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('update');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('update'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('updateAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPUTRequestWithoutProvidedResourceWillThrowAStatus400() {
		$throwStopException = function() { throw new \F3\FLOW3\MVC\Exception\StopActionException(); };

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
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
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('delete'));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('deleteAction'), array(), '', FALSE);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\StopActionException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aDELETERequestWithoutResourceWillThrowAStatus400() {
		$throwStopException = function() { throw new \F3\FLOW3\MVC\Exception\StopActionException(); };

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('F3\FLOW3\MVC\Controller\RestController', array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$result = $controller->_call('resolveActionMethodName');
	}

}
?>
