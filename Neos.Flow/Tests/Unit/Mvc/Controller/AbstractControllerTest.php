<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use Neos\Flow\Cli\Request;
use Neos\Flow\Cli\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
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
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Error\Messages as FlowError;

/**
 * Testcase for the MVC Abstract Controller
 */
final class AbstractControllerTest extends UnitTestCase
{
    /**
     * @var ActionResponse
     */
    protected $actionResponse;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    protected function setUp(): void
    {
        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);

        $this->actionResponse = new ActionResponse();

        $this->mockActionRequest = $this->createMock(ActionRequest::class);
        $this->mockActionRequest->method('getHttpRequest')->willReturn($mockHttpRequest);
    }

    #[Test]
    public function initializeControllerWillThrowAnExceptionIfTheGivenRequestIsNotSupported(): void
    {
        $request = new Request();
        $response = new Response();

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        try {
            $controller->_call('initializeController', $request, $response);
        } catch (\TypeError $error) {
            $this->assertInstanceOf(\TypeError::class, $error);
        }
    }

    #[Test]
    public function initializeControllerInitializesRequestUriBuilderArgumentsAndContext(): void
    {
        $request = ActionRequest::fromHttpRequest(new ServerRequest('GET', new Uri('http://localhost/foo')));

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);

        self::assertFalse($request->isDispatched());
        $controller->_call('initializeController', $request, $this->actionResponse);

        self::assertTrue($request->isDispatched());
        self::assertInstanceOf(Arguments::class, $controller->_get('arguments'));
        self::assertSame($request, $controller->_get('uriBuilder')->getRequest());
        self::assertSame($request, $controller->getControllerContext()->getRequest());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function addFlashMessageDataProvider(): \Iterator
    {
        yield [
            new FlowError\Message('MessageBody'),
            'MessageBody'
        ];
        yield [
            new FlowError\Message('Some Other Message', 123, ['foo' => 'bar'], 'Message Title'),
            'Some Other Message', 'Message Title', FlowError\Message::SEVERITY_OK, ['foo' => 'bar'], 123
        ];
        yield [
            new FlowError\Notice('Some Notice', 123, ['foo' => 'bar'], 'Message Title'),
            'Some Notice', 'Message Title', FlowError\Message::SEVERITY_NOTICE, ['foo' => 'bar'], 123
        ];
        yield [
            new FlowError\Warning('Some Warning', 123, ['foo' => 'bar'], 'Message Title'),
            'Some Warning', 'Message Title', FlowError\Message::SEVERITY_WARNING, ['foo' => 'bar'], 123
        ];
        yield [
            new FlowError\Error('Some Error', 123, ['foo' => 'bar'], 'Message Title'),
            'Some Error', 'Message Title', FlowError\Message::SEVERITY_ERROR, ['foo' => 'bar'], 123
        ];
    }

    #[DataProvider('addFlashMessageDataProvider')]
    #[Test]
    public function addFlashMessageTests($expectedMessage, $messageBody, $messageTitle = '', $severity = FlowError\Message::SEVERITY_OK, array $messageArguments = [], $messageCode = null): void
    {
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);

        $controllerContext = $this->createMock(ControllerContext::class);
        $controllerContext->method('getFlashMessageContainer')->willReturn($flashMessageContainer);
        $this->inject($controller, 'controllerContext', $controllerContext);

        $controller->addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
        self::assertEquals([$expectedMessage], $flashMessageContainer->getMessages());
    }

    #[Test]
    public function addFlashMessageThrowsExceptionOnInvalidMessageBody(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $flashMessageContainer = new FlashMessageContainer();
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);

        $controllerContext = $this->createMock(ControllerContext::class);
        $controllerContext->method('getFlashMessageContainer')->willReturn($flashMessageContainer);
        $this->inject($controller, 'controllerContext', $controllerContext);

        $controller->addFlashMessage(new \stdClass());
    }

    #[Test]
    public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

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

    #[Test]
    public function forwardResetsControllerArguments(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
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

    #[Test]
    public function forwardSetsSubpackageKeyIfNeeded(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with('MySubPackage');

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage\MySubPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
        }
    }

    #[Test]
    public function forwardResetsSubpackageKeyIfNotSetInPackageKey(): void
    {
        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->method('convertObjectsToIdentityArrays')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerActionName')->with('theTarget');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerName')->with('Bar');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerPackageKey')->with('MyPackage');
        $this->mockActionRequest->expects($this->atLeastOnce())->method('setControllerSubpackageKey')->with(null);

        try {
            $controller->_call('forward', 'theTarget', 'Bar', 'MyPackage', ['foo' => 'bar']);
        } catch (ForwardException $exception) {
        }
    }

    #[Test]
    public function forwardConvertsObjectsFoundInArgumentsIntoIdentifiersBeforePassingThemToRequest(): void
    {
        $originalArguments = ['foo' => 'bar', 'bar' => ['someObject' => new \stdClass()]];
        $convertedArguments = ['foo' => 'bar', 'bar' => ['someObject' => ['__identity' => 'x']]];

        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArguments)->willReturn($convertedArguments);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'persistenceManager', $mockPersistenceManager);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $this->mockActionRequest->expects($this->atLeastOnce())->method('setArguments')->with($convertedArguments);

        try {
            $controller->_call('forward', 'other', 'Bar', 'MyPackage', $originalArguments);
        } catch (ForwardException $exception) {
        }
    }

    #[Test]
    public function redirectRedirectsToTheSpecifiedAction(): void
    {
        $arguments = ['foo' => 'bar'];

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('doc')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', 'Duper\Package')->willReturn('the uri');

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        //$this->inject($controller, 'flashMessageContainer', new FlashMessageContainer());
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        $controller->expects($this->once())->method('redirectToUri')->with('the uri');
        $controller->_call('redirect', 'show', 'Stuff', 'Super\Duper\Package', $arguments, 0, 303, 'doc');
    }

    #[Test]
    public function redirectUsesRequestFormatAsDefaultAndUnsetsSubPackageKeyIfNecessary(): void
    {
        $arguments = ['foo' => 'bar'];

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getFormat')->willReturn('json');

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', $arguments, 'Stuff', 'Super', null)->willReturn('the uri');

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest', 'redirectToUri']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        $controller->expects($this->once())->method('redirectToUri')->with('the uri');
        $controller->_call('redirect', 'show', 'Stuff', 'Super', $arguments);
    }

    #[Test]
    public function redirectToUriThrowsStopActionException(): void
    {
        $this->expectException(StopActionException::class);
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->_call('redirectToUri', 'http://some.uri');
    }

    #[Test]
    public function redirectToUriSetsStatus(): void
    {
        /** @var AbstractController $controller */
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', 'http://some.uri');
        } catch (StopActionException $e) {
        }

        self::assertSame(303, $this->actionResponse->getStatusCode());
    }

    #[Test]
    public function redirectToUriSetsRedirectUri(): void
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', $uri);
        } catch (StopActionException $e) {
        }

        self::assertSame($uri, (string)$this->actionResponse->getRedirectUri());
    }

    #[Test]
    public function redirectToUriDoesNotSetLocationHeaderIfDelayIsNotZero(): void
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('redirectToUri', $uri, 10);
        } catch (StopActionException $e) {
        }

        self::assertNull($this->actionResponse->getRedirectUri());
    }

    #[Test]
    public function throwStatusSetsThrowsStopActionException(): void
    {
        $this->expectException(StopActionException::class);
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $controller->_call('throwStatus', 404);
    }

    #[Test]
    public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction(): void
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $message = '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>';

        try {
            $controller->_call('throwStatus', 404, 'File Really Not Found', $message);
        } catch (StopActionException $e) {
        }

        self::assertSame(404, $this->actionResponse->getStatusCode());
        self::assertSame($message, $this->actionResponse->getContent());
    }

    #[Test]
    public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided(): void
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('throwStatus', 404);
        } catch (StopActionException $e) {
        }

        self::assertSame(404, $this->actionResponse->getStatusCode());
        self::assertSame('404 Not Found', $this->actionResponse->getContent());
    }

    #[Test]
    public function mapRequestArgumentsToControllerArgumentsDoesJustThat(): void
    {
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->onlyMethods(['convert'])->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->willReturnArgument(0);

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $controller->_set('arguments', $controllerArguments);
        $matcher = self::atLeast(2);

        $this->mockActionRequest->expects($matcher)->method('hasArgument')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('baz', $parameters[0]);
            }
            return true;
        });
        $matcher = self::atLeast(2);
        $this->mockActionRequest->expects($matcher)->method('getArgument')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
                return 'bar';
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('baz', $parameters[0]);
                return 'quux';
            }
        });

        $controller->_call('mapRequestArgumentsToControllerArguments');
        self::assertEquals('bar', $controllerArguments['foo']->getValue());
        self::assertEquals('quux', $controllerArguments['baz']->getValue());
    }

    #[Test]
    public function mapRequestArgumentsToControllerArgumentsThrowsExceptionIfRequiredArgumentWasNotSet(): void
    {
        $this->expectException(RequiredArgumentMissingException::class);
        $mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->onlyMethods(['convert'])->getMock();
        $mockPropertyMapper->expects($this->atLeastOnce())->method('convert')->willReturnArgument(0);

        $controllerArguments = new Arguments();
        $controllerArguments->addNewArgument('foo', 'string', true);
        $controllerArguments->addNewArgument('baz', 'string', true);

        foreach ($controllerArguments as $controllerArgument) {
            $this->inject($controllerArgument, 'propertyMapper', $mockPropertyMapper);
        }

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);
        $controller->_set('arguments', $controllerArguments);
        $matcher = self::exactly(2);

        $this->mockActionRequest->expects($matcher)->method('hasArgument')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('foo', $parameters[0]);
                return true;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('baz', $parameters[0]);
                return false;
            }
        });
        $this->mockActionRequest->expects($this->once())->method('getArgument')->with('foo')->willReturn('bar');

        $controller->_call('mapRequestArgumentsToControllerArguments');
    }
}
