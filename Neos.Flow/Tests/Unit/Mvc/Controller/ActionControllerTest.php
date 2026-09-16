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
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionResponse;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Controller\Argument;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService;
use Neos\Flow\Mvc\Exception\InvalidActionVisibilityException;
use Neos\Flow\Mvc\Exception\NoSuchActionException;
use Neos\Flow\Mvc\Exception\ViewNotFoundException;
use Neos\Flow\Mvc\View\SimpleTemplateView;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Mvc\ViewConfigurationManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Flow\Validation\ValidatorResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for the MVC Action Controller
 */
final class ActionControllerTest extends UnitTestCase
{
    /**
     * @var ActionController
     */
    protected $actionController;

    /**
     * @var Mvc\ActionRequest
     */
    protected $mockRequest;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    protected function setUp(): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, []);

        $this->mockRequest = $this->createMock(ActionRequest::class);
        $this->mockRequest->method('getControllerPackageKey')->willReturn(('Some.Package'));
        $this->mockRequest->method('getControllerSubpackageKey')->willReturn(('Subpackage'));
        $this->mockRequest->method('getFormat')->willReturn(('theFormat'));
        $this->mockRequest->method('getControllerName')->willReturn(('TheController'));
        $this->mockRequest->method('getControllerActionName')->willReturn(('theAction'));
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->createStub(ControllerContext::class));
        $this->inject($this->actionController, 'viewConfigurationManager', $this->createStub(ViewConfigurationManager::class));
    }

    #[Test]
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithFormatSuffixIfItExists(): void
    {
        $this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->willReturn(('ResolvedObjectName'));

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    #[Test]
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithoutFormatSuffixIfItExists(): void
    {
        $matcher = self::exactly(2);
        $this->mockObjectManager->expects($matcher)->method('getCaseSensitiveObjectName')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('some\package\subpackage\view\thecontroller\theactiontheformat', $parameters[0]);
                return null;
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('some\package\subpackage\view\thecontroller\theaction', $parameters[0]);
                return 'ResolvedObjectName';
            }
        });

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    #[Test]
    public function resolveViewObjectNameRespectsViewFormatToObjectNameMap(): void
    {
        $this->actionController->_set('viewFormatToObjectNameMap', ['html' => 'Foo', 'theFormat' => 'Some\Custom\View\Object\Name']);
        $matcher = self::exactly(2);
        $this->mockObjectManager->expects($matcher)->method('getCaseSensitiveObjectName')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('some\package\subpackage\view\thecontroller\theactiontheformat', $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('some\package\subpackage\view\thecontroller\theaction', $parameters[0]);
            }
            return null;
        });

        self::assertSame('Some\Custom\View\Object\Name', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    #[Test]
    public function resolveViewReturnsViewResolvedByResolveViewObjectName(): void
    {
        $this->mockObjectManager->expects($this->atLeastOnce())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->willReturn((SimpleTemplateView::class));
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView', $this->mockRequest));
    }

    #[Test]
    public function resolveViewReturnsDefaultViewIfNoViewObjectNameCouldBeResolved(): void
    {
        $this->mockObjectManager->method('getCaseSensitiveObjectName')->willReturn((null));
        $this->actionController->_set('defaultViewObjectName', SimpleTemplateView::class);
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView', $this->mockRequest));
    }

    #[Test]
    public function processRequestThrowsExceptionIfRequestedActionIsNotCallable(): void
    {
        $this->expectException(NoSuchActionException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->createStub(ControllerContext::class));

        $mockRequest = $this->createMock(ActionRequest::class);
        $mockRequest->method('getControllerActionName')->willReturn(('nonExisting'));

        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockHttpRequest = $this->createStub(ServerRequestInterface::class);
        $mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new ActionResponse();

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    #[Test]
    public function processRequestThrowsExceptionIfRequestedActionIsNotPublic(): void
    {
        $this->expectException(InvalidActionVisibilityException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->createStub(ControllerContext::class));
        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockRequest = $this->createMock(ActionRequest::class);
        $mockRequest->method('getControllerActionName')->willReturn(('initialize'));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->method('isMethodPublic')->willReturnCallback(function (string $className, string $methodName): bool {
            if ($methodName === 'initializeAction') {
                return false;
            } else {
                return true;
            }
        });

        $this->mockObjectManager->method('get')->willReturnCallBack(function ($classname) use ($mockReflectionService) {
            if ($classname === ReflectionService::class) {
                return $mockReflectionService;
            }

            return $this->createMock($classname);
        });

        $mockHttpRequest = $this->createStub(ServerRequestInterface::class);
        $mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new ActionResponse();

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    #[Test]
    public function processRequestInjectsSettingsToView(): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->createStub(ControllerContext::class));
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $mockSettings = ['foo', 'bar'];
        $this->inject($this->actionController, 'settings', $mockSettings);

        $mockMvcPropertyMappingConfigurationService = $this->createStub(MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->createStub(ServerRequestInterface::class);
        $this->mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $matcher = self::exactly(2);
        $mockView->expects($matcher)->method('assign')
            ->willReturnCallback(function (string $key, $value) use ($matcher, $mockSettings, $mockView) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('settings', $key);
                    self::assertSame($mockSettings, $value);
                } else {
                    self::assertSame('request', $key);
                    self::assertSame($this->mockRequest, $value);
                }
                return $mockView;
            });
        $this->actionController->expects(self::once())->method('resolveView')->willReturn($mockView);
        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn(new Response());
        $this->actionController->expects(self::once())->method('resolveActionMethodName')->willReturn('someAction');

        $this->actionController->processRequest($this->mockRequest);
    }

    public static function supportedAndRequestedMediaTypes(): \Iterator
    {
        // supported, Accept header, expected
        yield [['application/json'], '*/*', 'application/json'];
        yield [['text/html', 'application/json'], 'application/json', 'application/json'];
        yield [['text/html'], 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8', 'text/html'];
        yield [['application/json', 'application/xml'], 'text/html, application/json;q=0.7, application/xml;q=0.9', 'application/xml'];
    }

    #[DataProvider('supportedAndRequestedMediaTypes')]
    #[Test]
    public function processRequestSetsNegotiatedContentTypeOnResponse($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createStub(MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn($acceptHeader);
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $this->actionController->expects($this->once())->method('callActionMethod')->willReturn(new Response());
        $this->inject($this->actionController, 'supportedMediaTypes', $supportedMediaTypes);

        $response = $this->actionController->processRequest($this->mockRequest);
        self::assertSame($expected, $response->getHeaderLine('Content-Type'));
    }

    #[DataProvider('supportedAndRequestedMediaTypes')]
    #[Test]
    public function processRequestUsesContentTypeFromActionResponse($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');
        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockResponse = new Response();
        $mockResponse = $mockResponse->withHeader('Content-Type', 'application/json');
        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn($mockResponse);

        $mockMvcPropertyMappingConfigurationService = $this->createStub(MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $response = $this->actionController->processRequest($this->mockRequest);
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    #[DataProvider('supportedAndRequestedMediaTypes')]
    #[Test]
    public function processRequestUsesContentTypeFromRenderedView($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView']);
        $this->actionController->method('resolveActionMethodName')->willReturn('theActionAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createStub(MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $mockView = $this->createMock(ViewInterface::class);
        $mockView->method('render')->willReturn(new Response(200, ['Content-Type' => 'application/json']));
        $this->actionController->expects($this->once())->method('resolveView')->with($this->mockRequest)->willReturn(($mockView));

        $mockResponse = $this->actionController->processRequest($this->mockRequest);
        self::assertSame('application/json', $mockResponse->getHeaderLine('Content-Type'));
    }

    #[Test]
    public function resolveViewThrowsExceptionIfResolvedViewDoesNotImplementViewInterface(): void
    {
        $this->expectException(ViewNotFoundException::class);
        $this->mockObjectManager->method('getCaseSensitiveObjectName')->willReturn((null));
        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $this->actionController->_call('resolveView', $this->mockRequest);
    }

    public static function ignoredValidationArgumentsProvider(): \Iterator
    {
        yield [false, false];
        yield [true, true];
    }

    #[DataProvider('ignoredValidationArgumentsProvider')]
    #[Test]
    public function initializeActionMethodValidatorsDoesNotAddValidatorForIgnoredArgumentsWithoutEvaluation($evaluateIgnoredValidationArgument, $setValidatorShouldBeCalled): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['getInformationNeededForInitializeActionMethodValidators']);

        $mockArgument = $this->createMock(Argument::class);
        $mockArgument->method('getName')->willReturn(('node'));
        $arguments = new Arguments();
        $arguments['node'] = $mockArgument;

        $ignoredValidationArguments = [
            'showAction' => [
                'node' => [
                    'evaluate' => $evaluateIgnoredValidationArgument
                ]
            ]
        ];

        $mockValidator = $this->createStub(ValidatorInterface::class);

        $parameterValidators = [
            'node' => $mockValidator
        ];

        $this->actionController->method('getInformationNeededForInitializeActionMethodValidators')->willReturn(([[], [], [], $ignoredValidationArguments]));

        $this->inject($this->actionController, 'actionMethodName', 'showAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockValidatorResolver = $this->createMock(ValidatorResolver::class);
        $mockValidatorResolver->method('getBaseValidatorConjunction')->willReturn(($this->createMock(ConjunctionValidator::class)));
        $mockValidatorResolver->method('buildMethodArgumentsValidatorConjunctions')->willReturn(($parameterValidators));
        $this->inject($this->actionController, 'validatorResolver', $mockValidatorResolver);

        if ($setValidatorShouldBeCalled) {
            $mockArgument->expects($this->once())->method('setValidator');
        } else {
            $mockArgument->expects($this->never())->method('setValidator');
        }

        $this->actionController->_call('initializeActionMethodValidators', $arguments);
    }
}

class ActionActionController extends ActionController
{
    public function theActionAction(): null
    {
        return null;
    }
}
