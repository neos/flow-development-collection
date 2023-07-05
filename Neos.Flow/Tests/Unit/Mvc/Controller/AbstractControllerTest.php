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

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Routing\ActionUriBuilderFactory;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\RouterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\AbstractController;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\RequiredArgumentMissingException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
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
     * @var ServerRequestInterface
     */
    protected $mockHttpRequest;

    /**
     * @var ActionResponse
     */
    protected $actionResponse;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var ActionUriBuilderFactory
     */
    protected $mockActionUriBuilderFactory;

    /**
     * @var RouterInterface|MockObject
     */
    protected $mockRouter;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->method('getUri')->willReturn(new Uri('http://localhost'));

        $this->actionResponse = new ActionResponse();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockActionRequest->method('getMainRequest')->willReturnSelf();

        $this->mockRouter = $this->getMockBuilder(RouterInterface::class)->getMock();
        $this->mockActionUriBuilderFactory = new ActionUriBuilderFactory($this->mockRouter);
    }

    /**
     * @test
     */
    public function initializeControllerWillThrowAnExceptionIfTheGivenRequestIsNotSupported()
    {
        $request = new Cli\Request();
        $response = new Cli\Response();

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        try {
            $controller->_call('initializeController', $request, $response);
        } catch (\TypeError $error) {
            $this->assertInstanceOf(\TypeError::class, $error);
        }
    }

    /**
     * @test
     */
    public function initializeControllerInitializesRequestUriBuilderArgumentsAndContext()
    {
        $request = ActionRequest::fromHttpRequest(new ServerRequest('GET', new Uri('http://localhost/foo')));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);

        self::assertFalse($request->isDispatched());
        $controller->_call('initializeController', $request, $this->actionResponse);

        self::assertTrue($request->isDispatched());
        self::assertInstanceOf(Arguments::class, $controller->_get('arguments'));
        self::assertSame($request, $controller->_get('uriBuilder')->getRequest());
        self::assertSame($request, $controller->getControllerContext()->getRequest());
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

        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $controllerContext->method('getFlashMessageContainer')->willReturn($flashMessageContainer);
        $this->inject($controller, 'controllerContext', $controllerContext);

        $controller->addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
        self::assertEquals([$expectedMessage], $flashMessageContainer->getMessages());
    }

    /**
     * @test
     */
    public function addFlashMessageThrowsExceptionOnInvalidMessageBody()
    {
        $this->expectException(\InvalidArgumentException::class);
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);

        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $controllerContext->method('getFlashMessageContainer')->willReturn($flashMessageContainer);
        $this->inject($controller, 'controllerContext', $controllerContext);

        $controller->addFlashMessage(new \stdClass());
    }

    /**
     * @test
     */
    public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setArguments')->with(['foo' => 'bar']);

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
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
        }

        if (!isset($exception)) {
            $this->fail('ForwardException was not thrown after calling forward()');
        }

        // all arguments of the current controller must be reset, in case the controller is called again later:
        $arguments = $controller->_get('arguments');
        self::assertFalse($arguments->hasArgument('foo'));
    }

    /**
     * @test
     */
    public function forwardSetsSubpackageKeyIfNeeded()
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerSubpackageKey')->with('MySubPackage');

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
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->will($this->returnArgument(0));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects(self::atLeastOnce())->method('setControllerSubpackageKey')->with(null);

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
        $mockPersistenceManager->expects(self::once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->willReturn($convertedArguments);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects(self::atLeastOnce())->method('setArguments')->with($convertedArguments);

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

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $mockUri = new Uri('the-uri');
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($mockUri) {
            $expectedRouteValues = [
                'foo' => 'bar',
                '@action' => 'show',
                '@controller' => 'stuff',
                '@package' => 'super',
                '@subpackage' => 'duper\package',
                '@format' => 'doc',
            ];
            self::assertEquals($expectedRouteValues, $resolveContext->getRouteValues());
            self::assertTrue($resolveContext->isForceAbsoluteUri());
            return $mockUri;
        });

        $controller->expects(self::once())->method('redirectToUri')->with($mockUri);
        $controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments, 0, 303, 'doc');
    }

    public function redirectMergesFallsBackToRequestPackageKeyAndControllerNameDataProvider(): \Traversable
    {
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => null,
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => 'PackageKey',
            'controllerName' => 'ControllerName',
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'packagekey', '@controller' => 'controllername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => null,
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => null,
            'controllerName' => 'ControllerName',
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'requestpackagekey', '@controller' => 'controllername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => null,
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => null,
            'controllerName' => null,
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'requestpackagekey', '@controller' => 'requestcontrollername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => 'RequestSubpackageKey',
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => null,
            'controllerName' => null,
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'requestpackagekey', '@subpackage' => 'requestsubpackagekey', '@controller' => 'requestcontrollername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => 'RequestSubpackageKey',
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => 'PackageKey\\SubpackageKey',
            'controllerName' => 'ControllerName',
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'packagekey', '@subpackage' => 'subpackagekey', '@controller' => 'controllername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => 'RequestSubpackageKey',
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => 'PackageKey',
            'controllerName' => 'ControllerName',
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'packagekey', '@controller' => 'controllername'],
        ];

        // Note: The following two tests merely document the current behavior
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => 'RequestSubpackageKey',
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => null,
            'controllerName' => 'ControllerName',
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'requestpackagekey', '@subpackage' => 'requestsubpackagekey', '@controller' => 'controllername'],
        ];
        yield [
            'requestPackageKey' => 'RequestPackageKey',
            'requestSubpackageKey' => 'RequestSubpackageKey',
            'requestControllerName' => 'RequestControllerName',
            'packageKey' => 'PackageKey',
            'controllerName' => null,
            'expectedRouteValues' => ['@action' => 'action', '@package' => 'packagekey', '@controller' => 'requestcontrollername'],
        ];
    }

    /**
     * @test
     * @dataProvider redirectMergesFallsBackToRequestPackageKeyAndControllerNameDataProvider
     */
    public function redirectMergesFallsBackToRequestPackageKeyAndControllerName(string $requestPackageKey, ?string $requestSubpackageKey, string $requestControllerName, ?string $packageKey, ?string $controllerName, array $expectedRouteValues): void
    {
        $this->mockActionRequest->method('getControllerPackageKey')->willReturn($requestPackageKey);
        $this->mockActionRequest->method('getControllerSubpackageKey')->willReturn($requestSubpackageKey);
        $this->mockActionRequest->method('getControllerName')->willReturn($requestControllerName);
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($expectedRouteValues) {
            self::assertEquals($expectedRouteValues, $resolveContext->getRouteValues());
            return new Uri();
        });
        $controller->_call('redirect', 'action', $controllerName, $packageKey);
    }


    /**
     * @test
     */
    public function redirectUsesRequestFormatAsDefaultAndUnsetsSubPackageKeyIfNecessary()
    {
        $arguments = ['foo' => 'bar'];

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getFormat')->willReturn('json');

        $mockUri = new Uri('the-uri');
        $this->mockRouter->expects(self::once())->method('resolve')->willReturnCallback(function (ResolveContext $resolveContext) use ($mockUri) {
            $expectedRouteValues = [
                'foo' => 'bar',
                '@action' => 'show',
                '@controller' => 'stuff',
                '@package' => 'super',
                '@format' => 'json',
            ];
            self::assertEquals($expectedRouteValues, $resolveContext->getRouteValues());
            self::assertTrue($resolveContext->isForceAbsoluteUri());
            return $mockUri;
        });

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->expects(self::once())->method('redirectToUri')->with($mockUri);
        $controller->_call('redirect', 'show', 'Stuff', 'Super', $arguments);
    }

    /**
     * @test
     */
    public function redirectRespectsSpecifiedDelayAndStatusCode(): void
    {
        $mockUri = new Uri('the-uri');
        $this->mockRouter->method('resolve')->willReturn($mockUri);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->expects(self::once())->method('redirectToUri')->with($mockUri, 123, 333);
        $controller->_call('redirect', 'show', 'Stuff', 'Super', [], 123, 333);
    }

    /**
     * @test
     */
    public function redirectToUriThrowsStopActionException()
    {
        $this->expectException(StopActionException::class);
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->_call('redirectToUri', 'http://some.uri');
    }

    /**
     * @test
     */
    public function redirectToUriSetsStatus()
    {
        /** @var AbstractController $controller */
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', 'http://some.uri');
        } catch (StopActionException $e) {
        }

        self::assertSame(303, $this->actionResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function redirectToUriSetsRedirectUri()
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', $uri);
        } catch (StopActionException $e) {
        }

        self::assertSame($uri, (string)$this->actionResponse->getRedirectUri());
    }

    /**
     * @test
     */
    public function redirectToUriDoesNotSetLocationHeaderIfDelayIsNotZero()
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', $uri, 10);
        } catch (StopActionException $e) {
        }

        self::assertNull($this->actionResponse->getRedirectUri());
    }

    /**
     * @test
     */
    public function redirectToUriRendersRedirectMetaTagIfDelayIsNotZero()
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', $uri, 123, 333);
        } catch (StopActionException $e) {
        }

        $expectedContent = '<html><head><meta http-equiv="refresh" content="123;url=http://flow.neos.io/awesomeness"/></head></html>';
        self::assertSame($expectedContent, $this->actionResponse->getContent());
        self::assertSame(333, $this->actionResponse->getStatusCode());
    }

    /**
     * @test
     */
    public function throwStatusSetsThrowsStopActionException()
    {
        $this->expectException(StopActionException::class);
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->_call('throwStatus', 404);
    }

    /**
     * @test
     */
    public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $message = '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>';

        try {
            $controller->_call('throwStatus', 404, 'File Really Not Found', $message);
        } catch (StopActionException $e) {
        }

        self::assertSame(404, $this->actionResponse->getStatusCode());
        self::assertSame($message, $this->actionResponse->getContent());
    }

    /**
     * @test
     */
    public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided()
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('throwStatus', 404);
        } catch (StopActionException $e) {
        }

        self::assertSame(404, $this->actionResponse->getStatusCode());
        self::assertSame('404 Not Found', $this->actionResponse->getContent());
    }

    /**
     * @test
     */
    public function mapRequestArgumentsToControllerArgumentsDoesJustThat()
    {
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->setMethods(['convert'])->getMock();
        $mockPropertyMapper->expects(self::atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $controller->_set('arguments', $controllerArguments);

        $this->mockActionRequest->expects(self::atLeast(2))->method('hasArgument')->withConsecutive(['foo'], ['baz'])->willReturn(true);
        $this->mockActionRequest->expects(self::atLeast(2))->method('getArgument')->withConsecutive(['foo'], ['baz'])->willReturnOnConsecutiveCalls('bar', 'quux');

        $controller->_call('mapRequestArgumentsToControllerArguments');
        self::assertEquals('bar', $controllerArguments['foo']->getValue());
        self::assertEquals('quux', $controllerArguments['baz']->getValue());
    }

    /**
     * @test
     */
    public function mapRequestArgumentsToControllerArgumentsThrowsExceptionIfRequiredArgumentWasNotSet()
    {
        $this->expectException(RequiredArgumentMissingException::class);
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->setMethods(['convert'])->getMock();
        $mockPropertyMapper->expects(self::atLeastOnce())->method('convert')->will($this->returnArgument(0));

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'actionUriBuilderFactory', $this->mockActionUriBuilderFactory);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $controller->_set('arguments', $controllerArguments);

        $this->mockActionRequest->expects(self::exactly(2))->method('hasArgument')->withConsecutive(['foo'], ['baz'])->willReturnOnConsecutiveCalls(true, false);
        $this->mockActionRequest->expects(self::once())->method('getArgument')->with('foo')->willReturn('bar');

        $controller->_call('mapRequestArgumentsToControllerArguments');
    }
}
