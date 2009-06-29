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
	public function processRequestSticksToSpecifiedSequence() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockURIBuilder = $this->getMock('F3\FLOW3\MVC\Web\Routing\URIBuilder');
		$mockURIBuilder->expects($this->once())->method('setRequest')->with($mockRequest);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\Routing\URIBuilder')->will($this->returnValue($mockURIBuilder));

		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array(
			'initializeFooAction', 'initializeAction', 'resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'initializeControllerArgumentsBaseValidators', 'resolveView', 'initializeView', 'callActionMethod'),
			array(), '', FALSE);
		$mockController->_set('objectFactory', $mockObjectFactory);
		$mockController->expects($this->at(0))->method('resolveActionMethodName')->will($this->returnValue('fooAction'));
		$mockController->expects($this->at(1))->method('initializeActionMethodArguments');
		$mockController->expects($this->at(2))->method('initializeActionMethodValidators');
		$mockController->expects($this->at(3))->method('initializeAction');
		$mockController->expects($this->at(4))->method('initializeFooAction');
		$mockController->expects($this->at(5))->method('initializeControllerArgumentsBaseValidators');
		$mockController->expects($this->at(6))->method('mapRequestArgumentsToControllerArguments');
		$mockController->expects($this->at(7))->method('resolveView')->will($this->returnValue($mockView));
		$mockController->expects($this->at(8))->method('initializeView');
		$mockController->expects($this->at(9))->method('callActionMethod');

		$mockController->processRequest($mockRequest, $mockResponse);
		$this->assertSame($mockRequest, $mockController->_get('request'));
		$this->assertSame($mockResponse, $mockController->_get('response'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodAppendsStringsReturnedByActionMethodToTheResponseObject() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockArguments = new \ArrayObject;

		$mockArgumentMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string'));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodRendersTheViewAutomaticallyIfTheActionReturnedNullAndAViewExists() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the view output');

		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');
		$mockView->expects($this->once())->method('render')->will($this->returnValue('the view output'));

		$mockArguments = new \ArrayObject;

		$mockArgumentMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_set('view', $mockView);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionMethodCallsTheErrorActionIfTheMappingResultsHaveErrors() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockArguments = new \ArrayObject;

		$mockArgumentMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(TRUE));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('barAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('barAction')->will($this->returnValue('the returned string'));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('errorMethodName', 'barAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function callActionMethodPassesDefaultValuesAsArguments() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockResponse = $this->getMock('F3\FLOW3\MVC\ResponseInterface', array(), array(), '', FALSE);

		$arguments = new \ArrayObject();
		$optionalArgument = new \F3\FLOW3\MVC\Controller\Argument('name1', 'Text');
		$optionalArgument->setDefaultValue('Default value');
		$arguments[] = $optionalArgument;

		$mockArgumentMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockArgumentMappingResults->expects($this->once())->method('hasErrors')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->with('Default value');
		$mockController->_set('request', $mockRequest);
		$mockController->_set('response', $mockResponse);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('argumentsMappingResults', $mockArgumentMappingResults);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function resolveViewUsesFluidTemplateViewIfTemplateIsAvailable() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockControllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext', array(), array(), '', FALSE);

		$mockFluidTemplateView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface', array('setControllerContext', 'getViewHelper', 'assign', 'render', 'hasTemplate'));
		$mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockFluidTemplateView->expects($this->once())->method('hasTemplate')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\Fluid\View\TemplateView')->will($this->returnValue($mockFluidTemplateView));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('buildControllerContext'), array(), '', FALSE);
		$mockController->expects($this->once())->method('buildControllerContext')->will($this->returnValue($mockControllerContext));
		$mockController->_set('session', $mockSession);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockFluidTemplateView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function resolveViewPreparesTheViewSpecifiedInTheRequestObjectAndUsesTheEmptyViewIfNoneCouldBeFound() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getControllerPackageKey')->will($this->returnValue('Foo'));
		$mockRequest->expects($this->at(1))->method('getControllerSubpackageKey')->will($this->returnValue(''));
		$mockRequest->expects($this->at(2))->method('getControllerName')->will($this->returnValue('Test'));
		$mockRequest->expects($this->at(3))->method('getControllerActionName')->will($this->returnValue('list'));
		$mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('html'));

		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockControllerContext = $this->getMock('F3\FLOW3\MVC\Controller\ControllerContext', array('getRequest'), array(), '', FALSE);
		$mockControllerContext->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		$mockFluidTemplateView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface', array('setControllerContext', 'getViewHelper', 'assign', 'render', 'hasTemplate'));
		$mockFluidTemplateView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockFluidTemplateView->expects($this->once())->method('hasTemplate')->will($this->returnValue(FALSE));
		$mockView = $this->getMock('F3\FLOW3\MVC\View\ViewInterface');
		$mockView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\Fluid\View\TemplateView')->will($this->returnValue($mockFluidTemplateView));
		$mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('f3\foo\view\test\listhtml')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(2))->method('getCaseSensitiveObjectName')->with('f3\foo\view\test\list')->will($this->returnValue(FALSE));
		$mockObjectManager->expects($this->at(3))->method('getObject')->with('F3\FLOW3\MVC\View\EmptyView')->will($this->returnValue($mockView));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('buildControllerContext'), array(), '', FALSE);
		$mockController->expects($this->once())->method('buildControllerContext')->will($this->returnValue($mockControllerContext));
		$mockController->_set('request', $mockRequest);
		$mockController->_set('session', $mockSession);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveViewObjectNameUsesViewObjectNamePatternToResolveViewObjectName() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$mockRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('MySubPackage'));
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));
		$mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('MyFormat'));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('randomviewobjectpattern\mypackage\mysubpackage\mycontroller\myaction\myformat');

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);
		$mockController->_set('viewObjectNamePattern', 'RandomViewObjectPattern\@package\@controller\@action\@format');

		$mockController->_call('resolveViewObjectName');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveViewObjectNameReturnsDefaultViewObjectNameIfNoCustomViewCanBeFound() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->exactly(2))->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);
		$mockController->_set('defaultViewObjectName', 'MyDefaultViewObjectName');

		$this->assertEquals('MyDefaultViewObjectName', $mockController->_call('resolveViewObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array('addNewArgument', 'removeAll'), array(), '', FALSE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('stringArgument', 'string', TRUE);
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('integerArgument', 'integer', TRUE);
		$mockArguments->expects($this->at(3))->method('addNewArgument')->with('objectArgument', 'F3\Foo\Bar', TRUE);

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$methodParameters = array(
			'stringArgument' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'string'
			),
			'integerArgument' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'integer'
			),
			'objectArgument' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'F3\Foo\Bar'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(1))->method('addNewArgument')->with('arg1', 'Text', TRUE);
		$mockArguments->expects($this->at(2))->method('addNewArgument')->with('arg2', 'array', FALSE, array(21));
		$mockArguments->expects($this->at(3))->method('addNewArgument')->with('arg3', 'Text', FALSE, 42);

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE
			),
			'arg2' => array(
				'position' => 1,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => TRUE,
				'defaultValue' => array(21),
				'allowsNull' => FALSE
			),
			'arg3' => array(
				'position' => 2,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => TRUE,
				'defaultValue' => 42,
				'allowsNull' => FALSE
			)
		);

		$methodTagsValues = array(
			'param' => array(
				'string $arg1',
				'array $arg2',
				'string $arg3'
			)
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->injectReflectionService($mockReflectionService);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeActionMethodValidatorsDetectsValidateAnnotationsAndRegistersNewValidatorsForEachArgument() {
		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('fooAction'), array(), '', FALSE);

		$chain1 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$chain2 = $this->getMock('F3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$validatorChains = array(
			'arg1' => $chain1,
			'arg2' => $chain2
		);

		$mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($validatorChains));

		$mockArgument = $this->getMock('F3\FLOW3\MVC\Controller\Argument', array(), array(), '', FALSE);
		$mockArgument->expects($this->at(0))->method('setValidator')->with($chain1);
		$mockArgument->expects($this->at(1))->method('setValidator')->with($chain1);

		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array(), array(), '', FALSE);
		$mockArguments->expects($this->at(0))->method('offsetExists')->with('arg1')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(1))->method('offsetGet')->with('arg1')->will($this->returnValue($mockArgument));
		$mockArguments->expects($this->at(2))->method('offsetExists')->with('arg2')->will($this->returnValue(TRUE));
		$mockArguments->expects($this->at(3))->method('offsetGet')->with('arg2')->will($this->returnValue($mockArgument));

		$mockController->injectValidatorResolver($mockValidatorResolver);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_set('arguments', $mockArguments);
		$mockController->_call('initializeActionMethodValidators');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function defaultErrorActionSetsArgumentMappingResultsErrorsInRequest() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$mockError = $this->getMock('F3\Error\Error', array('getMessage'), array(), '', FALSE);
		$mockArgumentsMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array('getErrors', 'getWarnings'), array(), '', FALSE);
		$mockArgumentsMappingResults->expects($this->atLeastOnce())->method('getErrors')->will($this->returnValue(array($mockError)));
		$mockArgumentsMappingResults->expects($this->any())->method('getWarnings')->will($this->returnValue(array()));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('pushFlashMessage'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('argumentsMappingResults', $mockArgumentsMappingResults);

		$mockRequest->expects($this->once())->method('setErrors')->with(array($mockError));

		$mockController->_call('errorAction');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function defaultErrorActionForwardsToReferrerIfSet() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface', array(), array(), '', FALSE);

		$arguments = array('foo' => 'bar');

		$mockArgumentsMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array('getErrors', 'getWarnings'), array(), '', FALSE);
		$mockArgumentsMappingResults->expects($this->any())->method('getErrors')->will($this->returnValue(array()));
		$mockArgumentsMappingResults->expects($this->any())->method('getWarnings')->will($this->returnValue(array()));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('pushFlashMessage', 'forward'), array(), '', FALSE);
		$mockController->_set('request', $mockRequest);
		$mockController->_set('argumentsMappingResults', $mockArgumentsMappingResults);

		$referrer = array(
			'actionName' => 'foo',
			'controllerName' => 'Bar',
			'packageKey' => 'Baz'
		);

		$mockRequest->expects($this->any())->method('hasArgument')->with('__referrer')->will($this->returnValue(TRUE));
		$mockRequest->expects($this->atLeastOnce())->method('getArgument')->with('__referrer')->will($this->returnValue($referrer));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue($arguments));

		$mockController->expects($this->once())->method('forward')->with('foo', 'Bar', 'Baz', $arguments);

		$mockController->_call('errorAction');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function defaultErrorActionCallsGetErrorFlashMessageAndPutsFlashMessage() {
		$this->markTestIncomplete('To be implemented');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function initializeActionMethodArgumentsResetsArgumentsBeforeAddingNewArguments() {
		$mockArguments = $this->getMock('F3\FLOW3\MVC\Controller\Arguments', array('removeAll'), array(), '', FALSE);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\Service', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnValue(array()));

		$mockController = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Controller\ActionController'), array('dummy'), array(), '', FALSE);
		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('arguments', $mockArguments);

		$mockArguments->expects($this->once())->method('removeAll');

		$mockController->_call('initializeActionMethodArguments');
	}
}
?>