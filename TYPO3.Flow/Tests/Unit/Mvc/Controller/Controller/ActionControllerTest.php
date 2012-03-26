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

use \TYPO3\FLOW3\Mvc\Controller\FlashMessage;
use \TYPO3\FLOW3\Error\Message;
use \TYPO3\FLOW3\Error\Notice;
use \TYPO3\FLOW3\Error\Warning;
use \TYPO3\FLOW3\Error\Error;

/**
 * Testcase for the MVC Action Controller
 *
 * @covers \TYPO3\FLOW3\Mvc\Controller\ActionController
 */
class ActionControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Mvc\Controller\Arguments
	 */
	protected $mockArguments;

	/**
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $mockRequest;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Web\Response
	 */
	protected $mockResponse;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\FLOW3\Mvc\FlashMessageContainer
	 */
	protected $mockFlashMessageContainer;

	public function setUp() {
		$this->mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('getValidationResults'), array(), '', FALSE);
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response', array(), array(), '', FALSE);
		$this->mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$this->mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 */
	public function processRequestSticksToSpecifiedSequence() {
		$this->mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue(NULL));
		$this->mockRequest->expects($this->once())->method('setFormat')->with('detectedformat');

		$mockView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array(
			'initializeFooAction', 'initializeAction', 'resolveActionMethodName', 'initializeActionMethodArguments',
			'initializeActionMethodValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext',
			'detectFormat', 'resolveView', 'initializeView', 'callActionMethod'),
			array(), '', TRUE);
		$mockController->expects($this->at(0))->method('resolveActionMethodName')->will($this->returnValue('fooAction'));
		$mockController->expects($this->at(1))->method('initializeActionMethodArguments');
		$mockController->expects($this->at(2))->method('initializeActionMethodValidators');
		$mockController->expects($this->at(3))->method('initializeAction');
		$mockController->expects($this->at(4))->method('initializeFooAction');
		$mockController->expects($this->at(5))->method('mapRequestArgumentsToControllerArguments');
		$mockController->expects($this->at(6))->method('detectFormat')->will($this->returnValue('detectedformat'));
		$mockController->expects($this->at(7))->method('resolveView')->will($this->returnValue($mockView));
		$mockController->expects($this->at(8))->method('initializeView');
		$mockController->expects($this->at(9))->method('callActionMethod');

		$mockController->_set('flashMessageContainer', $this->mockFlashMessageContainer);

		$mockController->processRequest($this->mockRequest, $this->mockResponse);

		$this->assertSame($this->mockRequest, $mockController->_get('uriBuilder')->getRequest());
		$this->assertSame($this->mockRequest, $mockController->_get('request'));
		$this->assertSame($this->mockResponse, $mockController->_get('response'));
	}

	protected function injectDependenciesIntoController($mockController) {
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('response', $this->mockResponse);
		$mockController->_set('arguments', $this->mockArguments);
		$mockController->_set('reflectionService', $this->mockReflectionService);
		$mockController->_set('flashMessageContainer', $this->mockFlashMessageContainer);
		$mockController->_set('actionMethodName', 'fooAction');
	}
	/**
	 * @test
	 */
	public function callActionMethodAppendsStringsReturnedByActionMethodToTheResponseObject() {
		$result = new \TYPO3\FLOW3\Error\Result();
		$this->mockArguments->expects($this->once())->method('getValidationResults')->will($this->returnValue($result));
		$this->mockResponse->expects($this->once())->method('appendContent')->with('the returned string');

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string'));

		$this->injectDependenciesIntoController($mockController);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 */
	public function callActionMethodRendersTheViewAutomaticallyIfTheActionReturnedNullAndAViewExists() {
		$result = new \TYPO3\FLOW3\Error\Result();
		$this->mockArguments->expects($this->once())->method('getValidationResults')->will($this->returnValue($result));

		$this->mockResponse->expects($this->once())->method('appendContent')->with('the view output');

		$mockView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');
		$mockView->expects($this->once())->method('render')->will($this->returnValue('the view output'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue(NULL));
		$this->injectDependenciesIntoController($mockController);
		$mockController->_set('view', $mockView);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 */
	public function callActionMethodCallsTheErrorActionIfTheArgumentsHaveErrors() {
		$this->mockResponse->expects($this->once())->method('appendContent')->with('the returned string from error action');

		$result = new \TYPO3\FLOW3\Error\Result();
		$result->forProperty('invalidArgument')->addError(new Error('asdf'));
		$this->mockArguments->expects($this->any())->method('getValidationResults')->will($this->returnValue($result));

		$this->mockReflectionService->expects($this->any())->method('getMethodAnnotations')->will($this->returnValue(array()));
		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('barAction', 'initializeAction'), array(), '', FALSE);
		$this->injectDependenciesIntoController($mockController);
		$mockController->_set('errorMethodName', 'barAction');

		$mockController->expects($this->once())->method('barAction')->will($this->returnValue('the returned string from error action'));

		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 */
	public function callActionMethodIgnoresArgumentsWithErrorsIfIgnoreValidationsIsUsed() {
		$this->mockResponse->expects($this->once())->method('appendContent')->with('the returned string from the action');

		$result = new \TYPO3\FLOW3\Error\Result();
		$result->forProperty('invalidArgument')->addError(new Error('asdf'));
		$this->mockArguments->expects($this->any())->method('getValidationResults')->will($this->returnValue($result));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('errorAction', 'fooAction', 'initializeAction'), array(), '', FALSE);
		$this->injectDependenciesIntoController($mockController);
		$mockController->_set('errorMethodName', 'errorAction');

		$this->mockReflectionService->expects($this->any())->method('getMethodAnnotations')->with(get_class($mockController), 'fooAction', 'TYPO3\FLOW3\Annotations\IgnoreValidation')->will($this->returnValue(array(new \TYPO3\FLOW3\Annotations\IgnoreValidation(array('value' => '$invalidArgument')))));

		$mockController->expects($this->never())->method('errorAction');
		$mockController->expects($this->once())->method('fooAction')->will($this->returnValue('the returned string from the action'));

		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 */
	public function callActionMethodPassesDefaultValuesAsArguments() {
		$result = new \TYPO3\FLOW3\Error\Result();
		$this->mockArguments->expects($this->once())->method('getValidationResults')->will($this->returnValue($result));

		$optionalArgument = new \TYPO3\FLOW3\Mvc\Controller\Argument('name1', 'string');
		$optionalArgument->setDefaultValue('Default value');
		$this->mockArguments[] = $optionalArgument;

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction', 'initializeAction'), array(), '', FALSE);
		$mockController->expects($this->once())->method('fooAction')->with('Default value');

		$this->injectDependenciesIntoController($mockController);
		$mockController->_call('callActionMethod');
	}

	/**
	 * @test
	 */
	public function detectFormatUsesTextFormatForNonWebRequests() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Cli\Request', array(), array(), '', FALSE);

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);

		$detectedFormat = $mockController->_call('detectFormat');
		$this->assertSame('txt', $detectedFormat);
	}

	/**
	 * @test
	 */
	public function detectFormatUsesHtmlFormatAsDefaultForWebRequests() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('getAcceptedFormats'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getAcceptedFormats')->will($this->returnValue(array('xml', 'json')));

		$this->mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('environment', $mockEnvironment);
		$mockController->_set('request', $this->mockRequest);

		$detectedFormat = $mockController->_call('detectFormat');
		$this->assertSame('html', $detectedFormat);
	}

	/**
	 * @test
	 */
	public function detectFormatUsesHtmlFormatAsDefaultForPostWebRequests() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('getAcceptedFormats'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getAcceptedFormats')->will($this->returnValue(array('xml', 'json')));

		$this->mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('environment', $mockEnvironment);
		$mockController->_set('request', $this->mockRequest);

		$detectedFormat = $mockController->_call('detectFormat');
		$this->assertSame('html', $detectedFormat);
	}

	/**
	 * @test
	 */
	public function detectFormatPrefersOtherFormatsThanHtmlIfControllerSupportsIt() {
		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('getAcceptedFormats'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getAcceptedFormats')->will($this->returnValue(array('html', 'json', 'xml')));

		$this->mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('environment', $mockEnvironment);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('supportedFormats', array('html', 'xml', 'json'));

		$detectedFormat = $mockController->_call('detectFormat');
		$this->assertSame('json', $detectedFormat);
	}

	/**
	 * @test
	 */
	public function resolveViewUsesResolvedViewIfItCanRenderTheCurrentAction() {
		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface');
		$mockControllerContext = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);

		$mockView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');
		$mockView->expects($this->once())->method('canRender')->with($mockControllerContext)->will($this->returnValue(TRUE));
		$mockView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\Foo\Bar\HTMLView')->will($this->returnValue($mockView));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('resolveViewObjectName'), array(), '', FALSE);
		$mockController->expects($this->once())->method('resolveViewObjectName')->will($this->returnValue('TYPO3\Foo\Bar\HTMLView'));

		$mockController->_set('session', $mockSession);
		$mockController->_set('controllerContext', $mockControllerContext);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 */
	public function resolveViewPreparesTheViewSpecifiedInTheRequestObject() {
		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface');
		$mockControllerContext = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);

		$mockView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');
		$mockView->expects($this->once())->method('canRender')->with($mockControllerContext)->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('ResolvedViewObjectName')->will($this->returnValue($mockView));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('resolveViewObjectName'), array(), '', FALSE);
		$mockController->expects($this->once())->method('resolveViewObjectName')->will($this->returnValue('ResolvedViewObjectName'));

		$mockController->_set('session', $mockSession);
		$mockController->_set('controllerContext', $mockControllerContext);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 */
	public function resolveViewReturnsTheNonFoundViewIfNoOtherViewCouldNotBeResolved() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));

		$mockSession = $this->getMock('TYPO3\FLOW3\Session\SessionInterface');
		$mockControllerContext = $this->getMock('TYPO3\FLOW3\Mvc\Controller\ControllerContext', array(), array(), '', FALSE);

		$mockOtherView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');
		$mockOtherView->expects($this->once())->method('canRender')->will($this->returnValue(FALSE));

		$mockNotFoundView = $this->getMock('TYPO3\FLOW3\Mvc\View\ViewInterface');
		$mockNotFoundView->expects($this->once())->method('setControllerContext')->with($mockControllerContext);
		$mockNotFoundView->expects($this->at(0))->method('assign')->with('errorMessage', 'No template was found. View could not be resolved for action "MyAction"');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->at(0))->method('get')->with('TYPO3\Fluid\View\TemplateView')->will($this->returnValue($mockOtherView));
		$mockObjectManager->expects($this->at(1))->method('get')->with('TYPO3\FLOW3\Mvc\View\NotFoundView')->will($this->returnValue($mockNotFoundView));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('resolveViewObjectName'), array(), '', FALSE);
		$mockController->expects($this->once())->method('resolveViewObjectName')->will($this->returnValue(FALSE));

		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('controllerContext', $mockControllerContext);
		$mockController->_set('session', $mockSession);
		$mockController->_set('objectManager', $mockObjectManager);

		$this->assertSame($mockNotFoundView, $mockController->_call('resolveView'));
	}

	/**
	 * @test
	 */
	public function resolveViewObjectNameUsesViewObjectNamePatternToResolveViewObjectName() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getControllerPackageKey')->will($this->returnValue('MyPackage'));
		$this->mockRequest->expects($this->once())->method('getControllerSubpackageKey')->will($this->returnValue('MySubPackage'));
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('MyController'));
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('MyAction'));
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('MyFormat'));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('randomviewobjectpattern\mypackage\mysubpackage\mycontroller\myaction\myformat');

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);
		$mockController->_set('viewObjectNamePattern', 'RandomViewObjectPattern\@package\@controller\@action\@format');

		$mockController->_call('resolveViewObjectName');
	}

	/**
	 * @test
	 */
	public function resolveViewObjectNameReturnsExplicitlyConfiguredFormatView() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getFormat')->will($this->returnValue('json'));

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->exactly(2))->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('objectManager', $mockObjectManager);
		$mockController->_set('viewFormatToObjectNameMap', array('json' => 'JsonViewObjectName'));

		$this->assertEquals('JsonViewObjectName', $mockController->_call('resolveViewObjectName'));
	}

	/**
	 * @test
	 */
	public function resolveActionMethodNameReturnsTheCurrentActionMethodNameFromTheRequest() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooBarAction'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);

		$this->assertEquals('fooBarAction', $mockController->_call('resolveActionMethodName'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\NoSuchActionException
	 */
	public function resolveActionMethodNameThrowsAnExceptionIfTheActionDefinedInTheRequestDoesNotExist() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('otherBarAction'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);

		$mockController->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function resolveActionMethodNameDoesNotThrowAnExceptionIfTheActionDefinedInTheRequestCanBeHandledByAMagicCallMethod() {
		$controllerClassName = 'TestController' . md5(uniqid(mt_rand(), TRUE));
		eval("
			class $controllerClassName extends \TYPO3\FLOW3\Mvc\Controller\ActionController {
					public function __call(\$methodName, array \$arguments) {
					}
			}
		");

		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest', array(), array(), '', FALSE);
		$this->mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('fooBar'));

		$mockController = $this->getAccessibleMock($controllerClassName, array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);

		$mockController->_call('resolveActionMethodName');
	}

	/**
	 * @test
	 */
	public function initializeActionMethodArgumentsRegistersArgumentsFoundInTheSignatureOfTheCurrentActionMethod() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface', array(), array(), '', FALSE);

		$arguments = new \TYPO3\FLOW3\Mvc\Controller\Arguments();

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction', 'evaluateDontValidateAnnotations'), array(), '', FALSE);

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
				'type' => 'TYPO3\Foo\Bar'
			)
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');

		$this->assertEquals(array('stringArgument', 'integerArgument', 'objectArgument'), $arguments->getArgumentNames());

		$argument = $arguments->getArgument('stringArgument');
		$this->assertEquals('stringArgument', $argument->getName());
		$this->assertEquals('string', $argument->getDataType());
		$this->assertEquals(TRUE, $argument->isRequired());

		$argument = $arguments->getArgument('integerArgument');
		$this->assertEquals('integerArgument', $argument->getName());
		$this->assertEquals('integer', $argument->getDataType());
		$this->assertEquals(TRUE, $argument->isRequired());

		$argument = $arguments->getArgument('objectArgument');
		$this->assertEquals('objectArgument', $argument->getName());
		$this->assertEquals('TYPO3\Foo\Bar', $argument->getDataType());
		$this->assertEquals(TRUE, $argument->isRequired());
	}

	/**
	 * @test
	 */
	public function initializeActionMethodArgumentsRegistersOptionalArgumentsAsSuch() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface', array(), array(), '', FALSE);

		$arguments = new \TYPO3\FLOW3\Mvc\Controller\Arguments();

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction', 'evaluateDontValidateAnnotations'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'type' => 'string'
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
				'allowsNull' => FALSE,
				'type' => 'string'
			)
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');

		$this->assertEquals(array('arg1', 'arg2', 'arg3'), $arguments->getArgumentNames());

		$argument = $arguments->getArgument('arg1');
		$this->assertEquals('arg1', $argument->getName());
		$this->assertEquals('string', $argument->getDataType());
		$this->assertEquals(TRUE, $argument->isRequired());

		$argument = $arguments->getArgument('arg2');
		$this->assertEquals('arg2', $argument->getName());
		$this->assertEquals('array', $argument->getDataType());
		$this->assertEquals(array(21), $argument->getDefaultValue());
		$this->assertEquals(FALSE, $argument->isRequired());

		$argument = $arguments->getArgument('arg3');
		$this->assertEquals('arg3', $argument->getName());
		$this->assertEquals('string', $argument->getDataType());
		$this->assertEquals(42, $argument->getDefaultValue());
		$this->assertEquals(FALSE, $argument->isRequired());
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Mvc\Exception\InvalidArgumentTypeException
	 */
	public function initializeActionMethodArgumentsThrowsExceptionIfDataTypeWasNotSpecified() {
		$this->mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface', array(), array(), '', FALSE);

		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE);

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$methodParameters = array(
			'arg1' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => FALSE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
			)
		);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodParameters')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodParameters));

		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('arguments', $mockArguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodArguments');
	}

	/**
	 * @test
	 */
	public function initializeActionMethodValidatorsCorrectlyRegistersValidatorsBasedOnDataType() {
		$this->markTestIncomplete('Sebastian -- fix after T3BOARD is long ago');
		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('getName'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));

		$arguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$mockValidatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));

		$mockController->_set('validatorResolver', $mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * @test
	 */
	public function initializeActionMethodValidatorsRegistersModelBasedValidators() {
		$this->markTestIncomplete('Sebastian -- fix after T3BOARD is long ago');
		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('getName', 'getDataType'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));
		$argument->expects($this->any())->method('getDataType')->will($this->returnValue('TYPO3\Foo\Quux'));

		$arguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodTagsValues = array(

		);

		$quuxBaseValidatorConjunction = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$quuxBaseValidatorConjunction->expects($this->once())->method('count')->will($this->returnValue(1));

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);
		$methodArgumentsValidatorConjunctions['arg1']->expects($this->once())->method('addValidator')->with($quuxBaseValidatorConjunction);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockValidatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));
		$mockValidatorResolver->expects($this->once())->method('getBaseValidatorConjunction')->with('TYPO3\Foo\Quux')->will($this->returnValue($quuxBaseValidatorConjunction));

		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('validatorResolver', $mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * @test
	 */
	public function initializeActionMethodValidatorsDoesNotRegisterModelBasedValidatorsIfDontValidateAnnotationIsSet() {
		$this->markTestIncomplete('Sebastian -- fix after T3BOARD is long ago');
		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('fooAction'), array(), '', FALSE);

		$argument = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('getName', 'getDataType'), array(), '', FALSE);
		$argument->expects($this->any())->method('getName')->will($this->returnValue('arg1'));
		$argument->expects($this->any())->method('getDataType')->will($this->returnValue('TYPO3\Foo\Quux'));

		$arguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('dummy'), array(), '', FALSE);
		$arguments->addArgument($argument);

		$methodTagsValues = array(
			'dontvalidate' => array(
				'$arg1'
			)
		);

		$methodArgumentsValidatorConjunctions = array();
		$methodArgumentsValidatorConjunctions['arg1'] = $this->getMock('TYPO3\FLOW3\Validation\Validator\ConjunctionValidator', array(), array(), '', FALSE);

		$mockReflectionService = $this->getMock('TYPO3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodTagsValues));

		$mockValidatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->once())->method('buildMethodArgumentsValidatorConjunctions')->with(get_class($mockController), 'fooAction')->will($this->returnValue($methodArgumentsValidatorConjunctions));
		$mockValidatorResolver->expects($this->any())->method('getBaseValidatorConjunction')->will($this->throwException(new \Exception("This should not be called because the dontvalidate annotation is set.")));

		$mockController->_set('reflectionService', $mockReflectionService);
		$mockController->_set('validatorResolver', $mockValidatorResolver);
		$mockController->_set('arguments', $arguments);
		$mockController->_set('actionMethodName', 'fooAction');
		$mockController->_call('initializeActionMethodValidators');

		$this->assertEquals($methodArgumentsValidatorConjunctions['arg1'], $arguments['arg1']->getValidator());
	}

	/**
	 * Data provider for error action test
	 * @return array
	 */
	public function dataProviderForErrorActionForward() {
		return array(
			'Simple use case, no sub package' => array(
				'action' => 'foo',
				'controller' => 'Bar',
				'package' => 'Baz',
				'subpackage' => NULL,
				'arguments' => array('a' => 'b'),
				'expectedForwardArguments' => array(
					'foo', 'Bar', 'Baz', array('a' => 'b')
				)
			),
			'Invocation with subpackage' => array(
				'action' => 'foo',
				'controller' => 'Bar',
				'package' => 'Baz',
				'subpackage' => 'MySubPackage',
				'arguments' => array('a' => 'b'),
				'expectedForwardArguments' => array(
					'foo', 'Bar', 'Baz\MySubPackage', array('a' => 'b')
				)
			)
		);
	}
	/**
	 * @test
	 * @dataProvider dataProviderForErrorActionForward
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function defaultErrorActionForwardsToReferrerIfSet($action, $controller, $package, $subpackage, $arguments, $expectedForwardArguments) {
		$this->mockArguments->expects($this->any())->method('getValidationResults')->will($this->returnValue(new \TYPO3\FLOW3\Error\Result()));

		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer', array(), array(), '', FALSE);

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('forward'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);
		$mockController->_set('arguments', $this->mockArguments);
		$mockController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$mockReferringRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$this->mockRequest->expects($this->any())->method('getReferringRequest')->will($this->returnValue($mockReferringRequest));

		$mockReferringRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($action));
		$mockReferringRequest->expects($this->any())->method('getControllerName')->will($this->returnValue($controller));
		$mockReferringRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue($package));
		$mockReferringRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue($subpackage));
		$mockReferringRequest->expects($this->any())->method('getArguments')->will($this->returnValue($arguments));

		$forwardMethodInvocationExpectation = call_user_func_array(array($mockController->expects($this->once())->method('forward'), 'with'), $expectedForwardArguments);
		$forwardMethodInvocationExpectation->will($this->throwException(new \TYPO3\FLOW3\Mvc\Exception\StopActionException('', 1234)));

		$mockController->_call('errorAction');
	}

	/**
	 * @test
	 */
	public function defaultErrorActionAddsFlashMessageToFlashMessageContainer() {
		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer', array(), array(), '', FALSE);
		$mockFlashMessageContainer->expects($this->once())->method('addMessage');

		$mockController = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\ActionController', array('dummy'), array(), '', FALSE);
		$mockController->_set('request', $this->mockRequest);
		$result = new \TYPO3\FLOW3\Error\Result();
		$result->addError(new Error('asdf'));
		$this->mockArguments->expects($this->once())->method('getValidationResults')->will($this->returnValue($result));
		$mockController->_set('arguments', $this->mockArguments);

		$mockController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$mockController->_call('errorAction');
	}
}
?>