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
 * Testcase for the MVC Abstract Controller
 *
 * @covers \TYPO3\FLOW3\Mvc\Controller\AbstractController
 */
class AbstractControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Mvc\Exception\UnsupportedRequestTypeException
	 */
	public function processRequestWillThrowAnExceptionIfTheGivenRequestIsNotSupported() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('mapRequestArgumentsToControllerArguments'), array($this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface')), '', FALSE);
		$controller->_set('supportedRequestTypes', array('TYPO3\Something\Request'));
		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequestAndBuildsTheControllerContext() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext'), array(), '', FALSE);
		$controller->_set('arguments', new \TYPO3\FLOW3\Mvc\Controller\Arguments());
		$controller->_set('flashMessageContainer', $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer'));
		$controller->processRequest($mockRequest, $mockResponse);

		$this->assertInstanceOf('TYPO3\FLOW3\Mvc\Controller\ControllerContext', $controller->getControllerContext());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardThrowsAStopActionException() {
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->will($this->returnValue(array()));
		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setDispatched')->with(FALSE);
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified() {
		$arguments = array('foo' => 'bar');
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardResetsArguments() {
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue(array()));
		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('removeAll'), array(), '', FALSE);
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);

		$mockArguments->expects($this->once())->method('removeAll');

		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardSetsSubpackageKeyIfNeeded() {
		$arguments = array('foo' => 'bar');

		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE);
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with('Blub');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz\\Blub', $arguments);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardResetsSubpackageKeyIfNeeded() {
		$arguments = array('foo' => 'bar');

		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array(), array(), '', FALSE);
		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnValue($arguments));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with('Baz');
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with(NULL);
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function forwardRemovesObjectsFromArgumentsBeforePassingThemToRequest() {
		$originalArguments = array('foo' => 'bar', 'bar' => array('someObject' => new \stdClass()));
		$convertedArguments = array('foo' => 'bar', 'bar' => array('someObject' => array('__identity' => 'x')));

		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->will($this->returnValue($convertedArguments));

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->once())->method('setArguments')->with($convertedArguments);

		$mockArguments = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Arguments', array('removeAll'), array(), '', FALSE);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('persistenceManager', $mockPersistenceManager);

		$controller->_call('forward', 'someAction', 'SomeController', 'SomePackage', $originalArguments);
	}

	/**
	 * @test
	 */
	public function redirectRedirectsToTheSpecifiedAction() {
		$arguments = array('foo' => 'bar');
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');

		$mockUriBuilder = $this->getMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->will($this->returnValue('the uri'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->expects($this->once())->method('redirectToUri')->with('the uri');
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments);
	}

	/**
	 * @test
	 */
	public function redirectUsesRequestFormatAsDefault() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('json'));

		$mockUriBuilder = $this->getMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->will($this->returnValue($mockUriBuilder));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_call('redirect', 'show');
	}

	/**
	 * @test
	 */
	public function redirectUsesGivenFormat() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->never())->method('getFormat');

		$mockUriBuilder = $this->getMock('TYPO3\FLOW3\Mvc\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('pdf')->will($this->returnValue($mockUriBuilder));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('redirectToUri'), array(), '', FALSE);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_set('request', $mockRequest);
		$controller->_call('redirect', 'show', NULL, NULL, NULL, 0, 303, 'pdf');
	}

	/**
	 * @test
	 */
	public function redirectToUriSetsStatus() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(303);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'));
		$controller->_set('response', $mockResponse);
		$controller->_set('request', $mockRequest);
		try {
			$controller->_call('redirectToUri', 'theUri', 1);
		} catch (\TYPO3\FLOW3\Mvc\Exception\StopActionException $exception) {}
	}


	/**
	 * @test
	 */
	public function redirectToUriUsesLocationHeaderOnlyIfDelayIsZero() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$mockResponse->expects($this->never())->method('setHeader');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'));
		$controller->_set('response', $mockResponse);
		$controller->_set('request', $mockRequest);
		try {
			$controller->_call('redirectToUri', 'theUri', 1);
		} catch (\TYPO3\FLOW3\Mvc\Exception\StopActionException $exception) {}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');

		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(404, 'File Really Not Found');
		$mockResponse->expects($this->once())->method('setContent')->with('<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);

		$controller->_call('throwStatus', 404, 'File Really Not Found', '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');
	}

	/**
	 * @test
	 */
	public function initializeControllerArgumentsBaseValidatorsRegistersValidatorsDeclaredInTheArgumentModels() {
		$mockValidators = array(
			'foo' => $this->getMock('TYPO3\FLOW3\Validation\Validator\ValidatorInterface'),
		);

		$mockValidatorResolver = $this->getMock('TYPO3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->at(0))->method('getBaseValidatorConjunction')->with('FooType')->will($this->returnValue($mockValidators['foo']));
		$mockValidatorResolver->expects($this->at(1))->method('getBaseValidatorConjunction')->with('BarType')->will($this->returnValue(NULL));

		$mockArgumentFoo = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array(), array('foo', 'FooType'));
		$mockArgumentFoo->expects($this->once())->method('getDataType')->will($this->returnValue('FooType'));
		$mockArgumentFoo->expects($this->once())->method('setValidator')->with($mockValidators['foo']);

		$mockArgumentBar = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array(), array('bar', 'barType'));
		$mockArgumentBar->expects($this->once())->method('getDataType')->will($this->returnValue('BarType'));
		$mockArgumentBar->expects($this->never())->method('setValidator');

		$mockArguments = new \TYPO3\FLOW3\Mvc\Controller\Arguments();
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->_set('validatorResolver', $mockValidatorResolver);
		$controller->_call('initializeControllerArgumentsBaseValidators');
	}

	/**
	 * @test
	 */
	public function mapRequestArgumentsToControllerArgumentsShouldPutRequestDataToArgumentObject() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');

		$argumentFoo = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('setValue'), array('foo', 'string'));
		$argumentFoo->setRequired(FALSE);
		$argumentBar = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('setValue'), array('bar', 'string'));
		$argumentBar->setRequired(FALSE);

		$argumentBar->expects($this->once())->method('setValue')->with('theBarArgumentValue');

		$arguments = new \TYPO3\FLOW3\Mvc\Controller\Arguments($mockObjectManager);
		$arguments->addArgument($argumentFoo);
		$arguments->addArgument($argumentBar);

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(FALSE));
		$mockRequest->expects($this->at(1))->method('hasArgument')->with('bar')->will($this->returnValue(TRUE));
		$mockRequest->expects($this->at(2))->method('getArgument')->with('bar')->will($this->returnValue('theBarArgumentValue'));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);

		$controller->_set('arguments', $arguments);
		$controller->_set('request', $mockRequest);

		$controller->_call('mapRequestArgumentsToControllerArguments');
	}

	/**
	 * @test
	 * @expectedException TYPO3\FLOW3\Mvc\Exception\RequiredArgumentMissingException
	 */
	public function mapRequestArgumentsToControllerArgumentsShouldThrowExceptionIfRequiredArgumentWasNotSet() {
		$argumentFoo = $this->getMock('TYPO3\FLOW3\Mvc\Controller\Argument', array('setValue'), array('foo', 'string'));
		$argumentFoo->setRequired(TRUE);

		$arguments = new \TYPO3\FLOW3\Mvc\Controller\Arguments();
		$arguments->addArgument($argumentFoo);

		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$mockRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(FALSE));

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('dummy'), array(), '', FALSE);

		$controller->_set('arguments', $arguments);
		$controller->_set('request', $mockRequest);

		$controller->_call('mapRequestArgumentsToControllerArguments');
	}

	/**
	 * @test
	 */
	public function addFlashMessageCreatesMessageByDefaultAndAddsItToFlashMessageContainer() {
		$expectedMessage = new \TYPO3\FLOW3\Error\Message('MessageBody');
		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer');
		$mockFlashMessageContainer->expects($this->once())->method('addMessage')->with($expectedMessage);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext'), array(), '', FALSE);
		$controller->_set('flashMessageContainer', $mockFlashMessageContainer);
		$controller->_call('addFlashMessage', 'MessageBody');
	}

	/**
	 * @test
	 */
	public function addFlashMessageDataProvider() {
		return array(
			array(
				new \TYPO3\FLOW3\Error\Message('MessageBody'),
				'MessageBody'
			),
			array(
				new \TYPO3\FLOW3\Error\Message('Some Other Message', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Other Message', 'Message Title', \TYPO3\FLOW3\Error\Message::SEVERITY_OK, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\FLOW3\Error\Notice('Some Notice', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Notice', 'Message Title', \TYPO3\FLOW3\Error\Message::SEVERITY_NOTICE, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\FLOW3\Error\Warning('Some Warning', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Warning', 'Message Title', \TYPO3\FLOW3\Error\Message::SEVERITY_WARNING, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\FLOW3\Error\Error('Some Error', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Error', 'Message Title', \TYPO3\FLOW3\Error\Message::SEVERITY_ERROR, array('foo' => 'bar'), 123
			),
		);
	}

	/**
	 * @test
	 * @dataProvider addFlashMessageDataProvider()
	 */
	public function addFlashMessageTests($expectedMessage, $messageBody, $messageTitle = '', $severity = \TYPO3\FLOW3\Error\Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = NULL) {
		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer');
		$mockFlashMessageContainer->expects($this->once())->method('addMessage')->with($expectedMessage);

		$controller = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Controller\AbstractController', array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext'), array(), '', FALSE);
		$controller->_set('flashMessageContainer', $mockFlashMessageContainer);
		$controller->_call('addFlashMessage', $messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
	}
}
?>