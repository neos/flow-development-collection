<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Http\Response as HttpResponse;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Error\Message;

/**
 * Testcase for the MVC Abstract Controller
 */
class AbstractControllerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
	 */
	public function initializeControllerWillThrowAnExceptionIfTheGivenRequestIsNotSupported() {
		$request = new \TYPO3\Flow\Cli\Request();
		$response = new \TYPO3\Flow\Cli\Response();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$controller->_call('initializeController', $request, $response);
	}

	/**
	 * @test
	 */
	public function initializeControllerInitializesRequestUriBuilderArgumentsAndContext() {
		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo')));
		$response = new HttpResponse();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());

		$this->assertFalse($request->isDispatched());
		$controller->_call('initializeController', $request, $response);

		$this->assertTrue($request->isDispatched());
		$this->assertInstanceOf('TYPO3\Flow\Mvc\Controller\Arguments', $controller->_get('arguments'));
		$this->assertSame($request, $controller->_get('uriBuilder')->getRequest());
		$this->assertSame($request, $controller->getControllerContext()->getRequest());
	}

	/**
	 * @return array
	 */
	public function addFlashMessageDataProvider() {
		return array(
			array(
				new \TYPO3\Flow\Error\Message('MessageBody'),
				'MessageBody'
			),
			array(
				new \TYPO3\Flow\Error\Message('Some Other Message', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Other Message', 'Message Title', \TYPO3\Flow\Error\Message::SEVERITY_OK, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\Flow\Error\Notice('Some Notice', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Notice', 'Message Title', \TYPO3\Flow\Error\Message::SEVERITY_NOTICE, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\Flow\Error\Warning('Some Warning', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Warning', 'Message Title', \TYPO3\Flow\Error\Message::SEVERITY_WARNING, array('foo' => 'bar'), 123
			),
			array(
				new \TYPO3\Flow\Error\Error('Some Error', 123, array('foo' => 'bar'), 'Message Title'),
				'Some Error', 'Message Title', \TYPO3\Flow\Error\Message::SEVERITY_ERROR, array('foo' => 'bar'), 123
			),
		);
	}

	/**
	 * @test
	 * @dataProvider addFlashMessageDataProvider()
	 */
	public function addFlashMessageTests($expectedMessage, $messageBody, $messageTitle = '', $severity = \TYPO3\Flow\Error\Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = NULL) {
		$flashMessageContainer = new FlashMessageContainer();
		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

		$controller->addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
		$this->assertEquals(array($expectedMessage), $flashMessageContainer->getMessages());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function addFlashMessageThrowsExceptionOnInvalidMessageBody() {
		$flashMessageContainer = new FlashMessageContainer();
		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

		$controller->addFlashMessage(new \stdClass());
	}

	/**
	 * @test
	 */
	public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$originalRequest = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo')));
		$this->inject($originalRequest, 'objectManager', $mockObjectManager);
		$response = new HttpResponse();

		$this->inject($originalRequest, 'objectManager', $mockObjectManager);

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$this->inject($controller, 'persistenceManager', $mockPersistenceManager);
		$controller->_call('initializeController', $originalRequest, $response);

		try {
			$controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', array('foo' => 'bar'));
		} catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
		}

		if (!isset($exception)) {
			$this->fail('ForwardException was not thrown after calling forward()');
		}
		$nextRequest = $exception->getNextRequest();

		$this->assertFalse($nextRequest->isDispatched());
		$this->assertEquals('MyPackage', $nextRequest->getControllerPackageKey());
		$this->assertEquals('theTarget', $nextRequest->getControllerActionName());
		$this->assertEquals('bar', $nextRequest->getArgument('foo'));

			// all arguments of the current controller must be reset, in case the controller is called again later:
		$arguments = $controller->_get('arguments');
		$this->assertFalse($arguments->hasArgument('foo'));
	}

	/**
	 * @test
	 */
	public function forwardSetsAndResetsSubpackageKeyIfNeeded() {
		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo')));
		$this->inject($request, 'objectManager', $mockObjectManager);
		$response = new HttpResponse();

		$this->inject($request, 'objectManager', $mockObjectManager);

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$this->inject($controller, 'persistenceManager', $mockPersistenceManager);
		$controller->_call('initializeController', $request, $response);

		try {
			$controller->_call('forward', 'theTarget', 'Bar', 'MyPackage\MySubPackage', array('foo' => 'bar'));
		} catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
		}

		$nextRequest = $exception->getNextRequest();

		$this->assertEquals('MyPackage', $nextRequest->getControllerPackageKey());
		$this->assertEquals('MySubPackage', $nextRequest->getControllerSubPackageKey());
		$this->assertEquals('theTarget', $nextRequest->getControllerActionName());
		$this->assertEquals('bar', $nextRequest->getArgument('foo'));

		try {
			$controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', array('foo' => 'bar'));
		} catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
		}

		$nextRequest = $exception->getNextRequest();

		$this->assertEquals('MyPackage', $nextRequest->getControllerPackageKey());
		$this->assertEquals(NULL, $nextRequest->getControllerSubPackageKey());
		$this->assertEquals('theTarget', $nextRequest->getControllerActionName());
		$this->assertEquals('bar', $nextRequest->getArgument('foo'));
	}

	/**
	 * @test
	 */
	public function forwardConvertsObjectsFoundInArgumentsIntoIdentifiersBeforePassingThemToRequest() {
		$originalArguments = array('foo' => 'bar', 'bar' => array('someObject' => new \stdClass()));
		$convertedArguments = array('foo' => 'bar', 'bar' => array('someObject' => array('__identity' => 'x')));

		$mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->will($this->returnValue($convertedArguments));

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(FALSE));

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo')));
		$this->inject($request, 'objectManager', $mockObjectManager);
		$response = new HttpResponse();

		$this->inject($request, 'objectManager', $mockObjectManager);

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$this->inject($controller, 'persistenceManager', $mockPersistenceManager);
		$controller->_call('initializeController', $request, $response);

		try {
			$controller->_call('forward', 'other', 'Bar', 'MyPackage', $originalArguments);
		} catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
		}

		$nextRequest = $exception->getNextRequest();
		$this->assertEquals($convertedArguments, $nextRequest->getArguments());
	}

	/**
	 * @test
	 */
	public function redirectRedirectsToTheSpecifiedAction() {
		$arguments = array('foo' => 'bar');

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo')));
		$response = new HttpResponse();

		$mockUriBuilder = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('doc')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->will($this->returnValue('the uri'));

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest', 'redirectToUri'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);
		$this->inject($controller, 'uriBuilder', $mockUriBuilder);

		$controller->expects($this->once())->method('redirectToUri')->with('the uri');
		$controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments, 0, 303, 'doc');
	}

	/**
	 * @test
	 */
	public function redirectUsesRequestFormatAsDefaultAndUnsetsSubPackageKeyIfNeccessary() {
		$arguments = array('foo' => 'bar');

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo.json')));
		$request->setFormat('json');
		$response = new HttpResponse();

		$mockUriBuilder = $this->getMock('TYPO3\Flow\Mvc\Routing\UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', NULL)->will($this->returnValue('the uri'));

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest', 'redirectToUri'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);
		$this->inject($controller, 'uriBuilder', $mockUriBuilder);

		$controller->expects($this->once())->method('redirectToUri')->with('the uri');
		$controller->_call('redirect', 'show', 'Stuff', 'Super', $arguments);
	}

	/**
	 * @test
	 */
	public function redirectToUriSetsStatus() {
		$uri = 'http://flow.typo3.org/awesomeness';

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo.json')));
		$response = new HttpResponse();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);

		$exceptionThrown = FALSE;
		try {
			$controller->_call('redirectToUri', $uri);
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
			$exceptionThrown = TRUE;
		}

		if (!$exceptionThrown) {
			$this->fail('No StopActionException thrown.');
		}

		$this->assertEquals('303', substr($response->getStatus(), 0, 3));
		$this->assertEquals($uri, $response->getHeader('Location'));
	}

	/**
	 * @test
	 */
	public function redirectToUriUsesLocationHeaderOnlyIfDelayIsZero() {
		$uri = 'http://flow.typo3.org/awesomeness';

		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo.json')));
		$response = new HttpResponse();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);

		try {
			$controller->_call('redirectToUri', $uri, 10, 301);
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
		}

		$this->assertEquals('301', substr($response->getStatus(), 0, 3));
		$this->assertNull($response->getHeader('Location'));
	}

	/**
	 * @test
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo.json')));
		$response = new HttpResponse();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);

		$message = '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>';

		$exceptionThrown = FALSE;
		try {
			$controller->_call('throwStatus', 404, 'File Really Not Found', $message);
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
			$exceptionThrown = TRUE;
		}

		if (!$exceptionThrown) {
			$this->fail('No StopActionException thrown.');
		}

		$this->assertEquals('404 File Really Not Found', $response->getStatus());
		$this->assertEquals($message, $response->getContent());
	}

	/**
	 * @test
	 */
	public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided() {
		$request = new ActionRequest(HttpRequest::create(new Uri('http://localhost/foo.json')));
		$response = new HttpResponse();

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);

		try {
			$controller->_call('throwStatus', 404);
		} catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
		}

		$this->assertEquals('404 Not Found', $response->getStatus());
		$this->assertEquals('404 Not Found', $response->getContent());
	}

	/**
	 * @test
	 */
	public function mapRequestArgumentsToControllerArgumentsDoesJustThat() {
		$mockPropertyMapper = $this->getMock('TYPO3\Flow\Property\PropertyMapper', array('convert'), array(), '', FALSE);
		$mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->will($this->returnArgument(0));

		$httpRequest = HttpRequest::create(new Uri('http://localhost/?foo=bar&baz=quux'));
		$request = $httpRequest->createActionRequest();
		$response = new HttpResponse();

		$controllerArguments = new Arguments();
		$controllerArguments->addNewArgument('foo', 'string', TRUE);
		$controllerArguments->addNewArgument('baz', 'string', TRUE);

		foreach ($controllerArguments as $controllerArgument) {
			$this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
		}

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);
		$controller->_set('arguments', $controllerArguments);

		$controller->_call('mapRequestArgumentsToControllerArguments');
		$this->assertEquals('bar', $controllerArguments['foo']->getValue());
		$this->assertEquals('quux', $controllerArguments['baz']->getValue());
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Mvc\Exception\RequiredArgumentMissingException
	 */
	public function mapRequestArgumentsToControllerArgumentsThrowsExceptionIfRequiredArgumentWasNotSet() {
		$httpRequest = HttpRequest::create(new Uri('http://localhost/'));
		$request = $httpRequest->createActionRequest();
		$response = new HttpResponse();

		$controllerArguments = new Arguments();
		$controllerArguments->addNewArgument('foo', 'string', TRUE);

		$controller = $this->getAccessibleMock('TYPO3\Flow\Mvc\Controller\AbstractController', array('processRequest'));
		$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
		$controller->_call('initializeController', $request, $response);
		$controller->_set('arguments', $controllerArguments);

		$controller->_call('mapRequestArgumentsToControllerArguments');
	}

}
?>