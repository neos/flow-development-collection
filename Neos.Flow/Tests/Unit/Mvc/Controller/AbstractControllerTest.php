<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Controller\AbstractController;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\FlashMessageContainer;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Cli;
use Neos\Error\Messages as FlowError;

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
        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('text/html'));

        $this->mockHttpResponse = $this->getMockBuilder(Response::class)->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function initializeControllerWillThrowAnExceptionIfTheGivenRequestIsNotSupported()
    {
        $request = new Cli\Request();
        $response = new Cli\Response();

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $request, $response);
    }

    /**
     * @test
     */
    public function initializeControllerInitializesRequestUriBuilderArgumentsAndContext()
    {
        $request = new ActionRequest(Request::create(new Uri('http://localhost/foo')));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());

        $this->assertFalse($request->isDispatched());
        $controller->_call('initializeController', $request, $this->mockHttpResponse);

        $this->assertTrue($request->isDispatched());
        $this->assertInstanceOf(Arguments::class, $controller->_get('arguments'));
        $this->assertSame($request, $controller->_get('uriBuilder')->getRequest());
        $this->assertSame($request, $controller->getControllerContext()->getRequest());
    }

    /**
     * @return array
     */
    public function addFlashMessageDataProvider()
    {
        return [
            [
                new FlowError\Message('MessageBody'),
                'MessageBody'
            ],
            [
                new FlowError\Message('Some Other Message', 123, ['foo' => 'bar'], 'Message Title'),
                'Some Other Message', 'Message Title', FlowError\Message::SEVERITY_OK, ['foo' => 'bar'], 123
            ],
            [
                new FlowError\Notice('Some Notice', 123, ['foo' => 'bar'], 'Message Title'),
                'Some Notice', 'Message Title', FlowError\Message::SEVERITY_NOTICE, ['foo' => 'bar'], 123
            ],
            [
                new FlowError\Warning('Some Warning', 123, ['foo' => 'bar'], 'Message Title'),
                'Some Warning', 'Message Title', FlowError\Message::SEVERITY_WARNING, ['foo' => 'bar'], 123
            ],
            [
                new FlowError\Error('Some Error', 123, ['foo' => 'bar'], 'Message Title'),
                'Some Error', 'Message Title', FlowError\Message::SEVERITY_ERROR, ['foo' => 'bar'], 123
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addFlashMessageDataProvider()
     */
    public function addFlashMessageTests($expectedMessage, $messageBody, $messageTitle = '', $severity = FlowError\Message::SEVERITY_OK, array $messageArguments = [], $messageCode = null)
    {
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

        $controller->addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
        $this->assertEquals([$expectedMessage], $flashMessageContainer->getMessages());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function addFlashMessageThrowsExceptionOnInvalidMessageBody()
    {
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'flashMessageContainer', $flashMessageContainer);

        $controller->addFlashMessage(new \stdClass());
    }

    /**
     * @test
     */
    public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setArguments')->with(['foo' => 'bar']);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
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
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
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
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with('MySubPackage');

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage\MySubPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function forwardResetsSubpackageKeyIfNotSetInPackageKey()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with(null);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function forwardConvertsObjectsFoundInArgumentsIntoIdentifiersBeforePassingThemToRequest()
    {
        $originalArguments = ['foo' => 'bar', 'bar' => ['someObject' => new \stdClass()]];
        $convertedArguments = ['foo' => 'bar', 'bar' => ['someObject' => ['__identity' => 'x']]];

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->will($this->returnValue($convertedArguments));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setArguments')->with($convertedArguments);

        try {
            $controller->_call('forward', 'other', 'Bar', 'MyPackage', $originalArguments);
        } catch (ForwardException $exception) {
        }
    }

    /**
     * @test
     */
    public function redirectRedirectsToTheSpecifiedAction()
    {
        $arguments = ['foo' => 'bar'];

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('doc')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->will($this->returnValue('the uri'));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
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
        $arguments = ['foo' => 'bar'];

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getFormat')->will($this->returnValue('json'));

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->will($this->returnValue($mockUriBuilder));
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', null)->will($this->returnValue('the uri'));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        $controller->expects($this->once())->method('redirectToUri')->with('the uri');
        $controller->_call('redirect', 'show', 'Stuff', 'Super', $arguments);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function redirectToUriThrowsStopActionException()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $controller->_call('redirectToUri', 'http://some.uri');
    }

    /**
     * @test
     */
    public function redirectToUriSetsStatus()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(303);

        try {
            $controller->_call('redirectToUri', 'http://some.uri');
        } catch (StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function redirectToUriSetsLocationHeader()
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setHeader')->with('Location', $uri);

        try {
            $controller->_call('redirectToUri', $uri);
        } catch (StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function redirectToUriDoesNotSetLocationHeaderIfDelayIsNotZero()
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->never())->method('setHeader');

        try {
            $controller->_call('redirectToUri', $uri, 10);
        } catch (StopActionException $e) {
        }
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function throwStatusSetsThrowsStopActionException()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $controller->_call('throwStatus', 404);
    }

    /**
     * @test
     */
    public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $message = '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>';

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(404, 'File Really Not Found');
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setContent')->with($message);

        try {
            $controller->_call('throwStatus', 404, 'File Really Not Found', $message);
        } catch (StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);

        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setStatus')->with(404, null);
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('getStatus')->will($this->returnValue('404 Not Found'));
        $this->mockHttpResponse->expects($this->atLeastOnce())->method('setContent')->with('404 Not Found');

        try {
            $controller->_call('throwStatus', 404);
        } catch (StopActionException $e) {
        }
    }

    /**
     * @test
     */
    public function mapRequestArgumentsToControllerArgumentsDoesJustThat()
    {
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->setMethods(['convert'])->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
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
     * @expectedException \Neos\Flow\Mvc\Exception\RequiredArgumentMissingException
     */
    public function mapRequestArgumentsToControllerArgumentsThrowsExceptionIfRequiredArgumentWasNotSet()
    {
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->setMethods(['convert'])->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->mockHttpResponse);
        $controller->_set('arguments', $controllerArguments);

        $this->mockActionRequest->expects($this->at(0))->method('hasArgument')->with('foo')->will($this->returnValue(true));
        $this->mockActionRequest->expects($this->at(1))->method('getArgument')->with('foo')->will($this->returnValue('bar'));
        $this->mockActionRequest->expects($this->at(2))->method('hasArgument')->with('baz')->will($this->returnValue(false));

        $controller->_call('mapRequestArgumentsToControllerArguments');
    }
}
