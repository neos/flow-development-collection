<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Mvc\FlashMessageContainer;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Abstract Controller
 */
class AbstractControllerTest extends UnitTestCase
{
    /**
     * @var Request
     */
    protected $mockHttpRequest;

    /**
     * @var Response
     */
    protected $mockHttpResponse;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('text/html'));

        $this->mockHttpResponse = $this->getMockBuilder(\TYPO3\Flow\Http\Response::class)->getMock();

        $this->mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeControllerWillThrowAnExceptionIfTheGivenRequestIsNotSupported()
    {
        $request = new \TYPO3\Flow\Cli\Request();
        $response = new \TYPO3\Flow\Cli\Response();

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $request, $response);
    }

    /**
     * @test
     */
    public function initializeControllerInitializesRequestUriBuilderArgumentsAndContext()
    {
        $request = new ActionRequest(Request::create(new Uri('http://localhost/foo')));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());

        $this->assertFalse($request->isDispatched());
        $controller->_call('initializeController', $request, $this->mockHttpResponse);

        $this->assertTrue($request->isDispatched());
        $this->assertInstanceOf(\TYPO3\Flow\Mvc\Controller\Arguments::class, $controller->_get('arguments'));
        $this->assertSame($request, $controller->_get('uriBuilder')->getRequest());
        $this->assertSame($request, $controller->getControllerContext()->getRequest());
    }

    /**
     * @return array
     */
    public function addFlashMessageDataProvider()
    {
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
    public function addFlashMessageTests($expectedMessage, $messageBody, $messageTitle = '', $severity = \TYPO3\Flow\Error\Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = null)
    {
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

        $controller->addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
        $this->assertEquals(array($expectedMessage), $flashMessageContainer->getMessages());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addFlashMessageThrowsExceptionOnInvalidMessageBody()
    {
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

        $controller->addFlashMessage(new \stdClass());
    }

    /**
     * @test
     */
    public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setArguments')->with(array('foo' => 'bar'));

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', array('foo' => 'bar'));
        } catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
        }

        if (!isset($exception)) {
            $this->fail('ForwardException was not thrown after calling forward()');
        }
    }

    /**
     * @test
     */
    public function forwardResetsControllerArguments()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', array('foo' => 'bar'));
        } catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
        }

        if (!isset($exception)) {
            $this->fail('ForwardException was not thrown after calling forward()');
        }

        // all arguments of the current controller must be reset, in case the controller is called again later:
        $arguments = $controller->_get('arguments');
        $this->assertFalse($arguments->hasArgument('foo'));
    }

    /**
     * @test
     */
    public function forwardSetsSubpackageKeyIfNeeded()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with('MySubPackage');

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage\MySubPackage', array('foo' => 'bar'));
        } catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function forwardResetsSubpackageKeyIfNotSetInPackageKey()
    {
        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with(null);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', array('foo' => 'bar'));
        } catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function forwardConvertsObjectsFoundInArgumentsIntoIdentifiersBeforePassingThemToRequest()
    {
        $originalArguments = array('foo' => 'bar', 'bar' => array('someObject' => new \stdClass()));
        $convertedArguments = array('foo' => 'bar', 'bar' => array('someObject' => array('__identity' => 'x')));

        $mockPersistenceManager = $this->createMock(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->will($this->returnValue($convertedArguments));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setArguments')->with($convertedArguments);

        try {
            $controller->_call('forward', 'other', 'Bar', 'MyPackage', $originalArguments);
        } catch (\TYPO3\Flow\Mvc\Exception\ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function redirectRedirectsToTheSpecifiedAction()
    {
        $arguments = array('foo' => 'bar');

        $mockUriBuilder = $this->createMock(\TYPO3\Flow\Mvc\Routing\UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('doc')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->will($this->returnValue('the uri'));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest', 'redirectToUri'));
        $this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        $controller->expects($this->once())->method('redirectToUri')->with('the uri');
        $controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments, 0, 303, 'doc');
    }

    /**
     * @test
     */
    public function redirectUsesRequestFormatAsDefaultAndUnsetsSubPackageKeyIfNecessary()
    {
        $arguments = array('foo' => 'bar');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('json'));

        $mockUriBuilder = $this->createMock(\TYPO3\Flow\Mvc\Routing\UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', null)->will($this->returnValue('the uri'));

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest', 'redirectToUri'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        $controller->expects($this->once())->method('redirectToUri')->with('the uri');
        $controller->_call('redirect', 'show', 'Stuff', 'Super', $arguments);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\StopActionException
     */
    public function redirectToUriThrowsStopActionException()
    {
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $controller->_call('redirectToUri', 'http://some.uri');
    }

    /**
     * @test
     */
    public function redirectToUriSetsStatus()
    {
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(303);

        try {
            $controller->_call('redirectToUri', 'http://some.uri');
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function redirectToUriSetsLocationHeader()
    {
        $uri = 'http://flow.typo3.org/awesomeness';

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setHeader')->with('Location', $uri);

        try {
            $controller->_call('redirectToUri', $uri);
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function redirectToUriDoesNotSetLocationHeaderIfDelayIsNotZero()
    {
        $uri = 'http://flow.typo3.org/awesomeness';

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->never())->method('setHeader');

        try {
            $controller->_call('redirectToUri', $uri, 10);
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
        }
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\StopActionException
     */
    public function throwStatusSetsThrowsStopActionException()
    {
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $controller->_call('throwStatus', 404);
    }

    /**
     * @test
     */
    public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction()
    {
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $message = '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>';

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(404, 'File Really Not Found');
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setContent')->with($message);

        try {
            $controller->_call('throwStatus', 404, 'File Really Not Found', $message);
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided()
    {
        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(404, null);
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('getStatus')->will($this->returnValue('404 Not Found'));
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setContent')->with('404 Not Found');

        try {
            $controller->_call('throwStatus', 404);
        } catch (\TYPO3\Flow\Mvc\Exception\StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function mapRequestArgumentsToControllerArgumentsDoesJustThat()
    {
        $mockPropertyMapper = $this->getMockBuilder(\TYPO3\Flow\Property\PropertyMapper::class)->disableOriginalConstructor()->setMethods(array('convert'))->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $controller->_set('arguments', $controllerArguments);

        $this->mockActionRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(true));
        $this->mockActionRequest->expects($this->at(1))->method('getArgument')->with('foo')->will($this->returnValue('bar'));
        $this->mockActionRequest->expects($this->at(2))->method('hasArgument')->with('baz')->will($this->returnValue(true));
        $this->mockActionRequest->expects($this->at(3))->method('getArgument')->with('baz')->will($this->returnValue('quux'));

        $controller->_call('mapRequestArgumentsToControllerArguments');
        $this->assertEquals('bar', $controllerArguments['foo']->getValue());
        $this->assertEquals('quux', $controllerArguments['baz']->getValue());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\RequiredArgumentMissingException
     */
    public function mapRequestArgumentsToControllerArgumentsThrowsExceptionIfRequiredArgumentWasNotSet()
    {
        $mockPropertyMapper = $this->getMockBuilder(\TYPO3\Flow\Property\PropertyMapper::class)->disableOriginalConstructor()->setMethods(array('convert'))->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\AbstractController::class, array('processRequest'));
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $controller->_set('arguments', $controllerArguments);

        $this->mockActionRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(true));
        $this->mockActionRequest->expects($this->at(1))->method('getArgument')->with('foo')->will($this->returnValue('bar'));
        $this->mockActionRequest->expects($this->at(2))->method('hasArgument')->with('baz')->will($this->returnValue(false));

        $controller->_call('mapRequestArgumentsToControllerArguments');
    }
}
