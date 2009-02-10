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

/**
 * Testcase for the MVC Action Controller
 *
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ActionControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodAppendsStringsReturnedByActionMethodToTheResponseObject() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockArguments = new \ArrayObject;

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string'));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodRendersTheViewAutomaticallyIfTheActionReturnedNullAndAViewExists() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the view output');

		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');
		$mockView->expects($this->once())->method('render')->will($this->returnValue('the view output'));

		$mockArguments = new \ArrayObject;

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('view', $mockView);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function initializeViewPreparesTheViewSpecifiedInTheRequestObjectAndUsesTheEmptyViewIfNoneCouldBeFound() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getViewObjectName')->will($this->returnValue('Foo'));
		$mockRequest->expects($this->at(1))->method('getViewObjectName')->will($this->returnValue(FALSE));

		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');
		$mockView->expects($this->exactly(2))->method('setRequest')->with($mockRequest);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('Foo')->will($this->returnValue($mockView));
		$mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\MVC\View\EmptyView')->will($this->returnValue($mockView));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);

		$mockController->_call('initializeView');
		$mockController->_call('initializeView');

		$this->assertSame($mockView, $mockController->_get('view'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('addNewArgument')->with('stringArgument', 'Text');
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('integerArgument', 'Integer');

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$methodParameters = array(
			'stringArgument' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE
			),
			'integerArgument' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE
			)
		);

		$methodTagsValues = array(
			'something' => array('confusing'),
			'param' => array(
				'string $firstArgument This is the first argument',
				'integer $secondArgument This is the second argument',
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeArguments');
	}
}
?>