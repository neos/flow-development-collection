<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Controller;
/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC REST Controller
 *
 */
class RestControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function resolveActionMethodNameOnlyResolvesRESTMethodNamesIfTheActionNameIsIndex() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->exactly(2))->method('getControllerActionName')->will($this->returnValue('foo'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('fooAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');

		$this->assertSame('fooAction', $result);
	}

	/**
	 * @test
	 */
	public function actionNameForGETRequestsWithoutProvidedResourceIsList() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('list');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('list'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('listAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function actionNameForGETRequestsWithProvidedResourceIsShow() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('show');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('show'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('showAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function actionNameForPOSTRequestsIsCreate() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('create');

		$mockRequest->expects($this->at(3))->method('getControllerActionName')->will($this->returnValue('create'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('createAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function actionNameForPUTRequestsIsUpdate() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('update');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('update'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('updateAction'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function aPUTRequestWithoutProvidedResourceWillThrowAStatus400() {
		$throwStopException = function() { throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException(); };

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function actionNameForDELETERequestsIsDelete() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(TRUE));

			// This is the important expectation:
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('delete');

		$mockRequest->expects($this->at(4))->method('getControllerActionName')->will($this->returnValue('delete'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('deleteAction'), array(), '', FALSE);
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$controller->_set('request', $mockRequest);
		$result = $controller->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function aDELETERequestWithoutResourceWillThrowAStatus400() {
		$throwStopException = function() { throw new \TYPO3\FLOW3\Mvc\Exception\StopActionException(); };

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response', array(), array(), '', FALSE);

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerActionName')->will($this->returnValue('index'));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockRequest->expects($this->once())->method('hasArgument')->with('customResourceArgumentName')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\RestController', array('throwStatus'), array(), '', FALSE);
		$controller->expects($this->once())->method('throwStatus')->with(400)->will($this->returnCallBack(array($throwStopException, '__invoke')));
		$controller->_set('resourceArgumentName', 'customResourceArgumentName');
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$result = $controller->_call('resolveActionMethodName');
	}

}
?>
