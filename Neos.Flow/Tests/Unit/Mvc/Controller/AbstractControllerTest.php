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
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Error\Messages as FlowError;
use Neos\Flow\Cli\Request;
use Neos\Flow\Cli\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\AbstractController;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Exception\ForwardException;
use Neos\Flow\Mvc\Exception\RequiredArgumentMissingException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\FlashMessage\FlashMessageContainer;
use Neos\Flow\Mvc\Routing\RouteValuesNormalizerInterface;
use Neos\Flow\Mvc\Routing\UriBuilder;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $controller->_call('initializeController', $request, $this->actionResponse);

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
        $routeValuesNormalizer = $this->createMock(RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'routeValuesNormalizer', $routeValuesNormalizer);
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
        $routeValuesNormalizer = $this->createMock(RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'routeValuesNormalizer', $routeValuesNormalizer);
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
        $routeValuesNormalizer = $this->createMock(RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'routeValuesNormalizer', $routeValuesNormalizer);
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
        $routeValuesNormalizer = $this->createMock(RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->willReturnArgument(0);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'routeValuesNormalizer', $routeValuesNormalizer);
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

        $routeValuesNormalizer = $this->createMock(RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->with($originalArguments)->willReturn($convertedArguments);

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $this->inject($controller, 'routeValuesNormalizer', $routeValuesNormalizer);
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
        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('doc')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', ['foo' => 'bar'], 'Stuff', 'Super', 'Duper\Package')->willReturn('the_uri');

        $controller = new class () extends AbstractController {
            public function processRequest(ActionRequest $request): ResponseInterface
            {
                $response = new ActionResponse();
                $mockUriBuilder = $this->uriBuilder;
                $this->initializeController($request, $response);
                $this->uriBuilder = $mockUriBuilder;

                $this->myIndexAction();

                return $this->response->buildHttpResponse();
            }

            public function myIndexAction(): void
            {
                $this->redirect('show', 'Stuff', 'Super\Duper\Package', ['foo' => 'bar'], 0, 303, 'doc');
            }
        };

        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        try {
            $controller->processRequest($this->mockActionRequest);
        } catch (StopActionException $exception) {
            $actionResponse = $exception->response;
            Assert::assertSame('the_uri', $actionResponse->getHeaderLine('Location'));
            Assert::assertSame(303, $actionResponse->getStatusCode());
            return;
        }
        Assert::assertTrue(false, 'Expected to be redirected.');
    }

    #[Test]
    public function redirectUsesRequestFormatAsDefaultAndUnsetsSubPackageKeyIfNecessary(): void
    {
        $this->mockActionRequest->expects($this->atLeastOnce())->method('getFormat')->willReturn('json');

        $mockUriBuilder = $this->createMock(UriBuilder::class);
        $mockUriBuilder->expects($this->once())->method('reset')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setFormat')->with('json')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->willReturn($mockUriBuilder);
        $mockUriBuilder->expects($this->once())->method('uriFor')->with('show', ['foo' => 'bar'], 'Stuff', 'Super', null)->willReturn('the_uri');

        $controller = new class () extends AbstractController {
            public function processRequest(ActionRequest $request): ResponseInterface
            {
                $response = new ActionResponse();
                $mockUriBuilder = $this->uriBuilder;
                $this->initializeController($request, $response);
                $this->uriBuilder = $mockUriBuilder;

                $this->myIndexAction();

                return $this->response->buildHttpResponse();
            }

            public function myIndexAction(): void
            {
                $this->redirect('show', 'Stuff', 'Super', ['foo' => 'bar']);
            }
        };

        $this->inject($controller, 'uriBuilder', $mockUriBuilder);

        try {
            $controller->processRequest($this->mockActionRequest);
        } catch (StopActionException $exception) {
            $actionResponse = $exception->response;
            Assert::assertSame('the_uri', $actionResponse->getHeaderLine('Location'));
            Assert::assertSame(303, $actionResponse->getStatusCode());
            return;
        }
        Assert::assertTrue(false, 'Expected to be redirected.');
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

        $response = null;
        try {
            $controller->_call('redirectToUri', 'http://some.uri');
        } catch (StopActionException $e) {
            // The dispatcher takes the response from the exception, so it makes sense to check that
            $response = $e->response;
        }

        self::assertNotNull($response);
        self::assertSame(303, $response->getStatusCode());
    }

    #[Test]
    public function redirectToUriSetsRedirectUri(): void
    {
        $uri = 'http://flow.neos.io/awesomeness';

        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        $response = null;
        try {
            $controller->_call('redirectToUri', $uri);
        } catch (StopActionException $e) {
            // The dispatcher takes the response from the exception, so it makes sense to check that
            $response = $e->response;
        }

        self::assertNotNull($response);
        self::assertSame($uri, $response->getHeaderLine('Location'));
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
            self::assertSame(404, $e->response->getStatusCode());
            self::assertSame($message, $e->response->getBody()->getContents());
            return;
        }

        self::fail('Expected throwStatus to throw.');
    }

    #[Test]
    public function throwStatusSetsTheStatusMessageAsContentIfNoFurtherContentIsProvided(): void
    {
        $controller = $this->getAccessibleMock(AbstractController::class, ['processRequest']);
        $controller->_call('initializeController', $this->mockActionRequest, $this->actionResponse);

        try {
            $controller->_call('throwStatus', 404);
        } catch (StopActionException $e) {
            self::assertSame(404, $e->response->getStatusCode());
            self::assertSame('404 Not Found', $e->response->getBody()->getContents());
            return;
        }

        self::fail('Expected throwStatus to throw.');
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

        $this->mockActionRequest->expects(self::atLeast(2))->method('hasArgument')
            ->willReturnCallback(function (string $argumentName) {
                self::assertContains($argumentName, ['foo', 'baz']);
                return true;
            });
        $this->mockActionRequest->expects(self::atLeast(2))->method('getArgument')
            ->willReturnCallback(fn (string $argumentName) => match ($argumentName) {
                'foo' => 'bar',
                'baz' => 'quux',
            });

        $controller->_call('mapRequestArgumentsToControllerArguments', $this->mockActionRequest, $controllerArguments);
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

        $this->mockActionRequest->expects(self::exactly(2))->method('hasArgument')
            ->willReturnCallback(fn (string $argumentName) => match ($argumentName) {
                'foo' => true,
                'baz' => false,
            });
        $this->mockActionRequest->expects($this->once())->method('getArgument')->with('foo')->willReturn('bar');

        $controller->_call('mapRequestArgumentsToControllerArguments', $this->mockActionRequest, $controllerArguments);
    }
}
