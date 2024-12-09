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

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Controller\Arguments;
use Neos\Flow\Mvc\View\SimpleTemplateView;
use Neos\Flow\Mvc;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Validation\Validator\ConjunctionValidator;
use Neos\Flow\Validation\Validator\ValidatorInterface;
use Neos\Flow\Validation\ValidatorResolver;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for the MVC Action Controller
 */
class ActionControllerTest extends UnitTestCase
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

    /**
     * @var Mvc\ViewConfigurationManager
     */
    protected $mockViewConfigurationManager;

    /**
     * @var Mvc\Controller\ControllerContext
     */
    protected $mockControllerContext;

    protected function setUp(): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, []);

        $this->mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockRequest->method('getControllerPackageKey')->willReturn(('Some.Package'));
        $this->mockRequest->method('getControllerSubpackageKey')->willReturn(('Subpackage'));
        $this->mockRequest->method('getFormat')->willReturn(('theFormat'));
        $this->mockRequest->method('getControllerName')->willReturn(('TheController'));
        $this->mockRequest->method('getControllerActionName')->willReturn(('theAction'));
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $this->mockControllerContext = $this->getMockBuilder(Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $this->mockViewConfigurationManager = $this->createMock(Mvc\ViewConfigurationManager::class);
        $this->inject($this->actionController, 'viewConfigurationManager', $this->mockViewConfigurationManager);
    }

    /**
     * @test
     */
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithFormatSuffixIfItExists(): void
    {
        $this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->willReturn(('ResolvedObjectName'));

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithoutFormatSuffixIfItExists(): void
    {
        $matcher = $this->exactly(2);
        $this->mockObjectManager->expects($matcher)->method('getCaseSensitiveObjectName')
            ->willReturnCallback(function ($name) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('some\package\subpackage\view\thecontroller\theactiontheformat', $name);
                    return null;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('some\package\subpackage\view\thecontroller\theaction', $name);
                    return 'ResolvedObjectName';
                }
                return 'unexpected invocation';
            });

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameRespectsViewFormatToObjectNameMap(): void
    {
        $this->actionController->_set('viewFormatToObjectNameMap', ['html' => 'Foo', 'theFormat' => 'Some\Custom\View\Object\Name']);
        $matcher = $this->exactly(2);
        $this->mockObjectManager->expects($matcher)->method('getCaseSensitiveObjectName')
            ->willReturnCallback(function ($name) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('some\package\subpackage\view\thecontroller\theactiontheformat', $name);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    $this->assertSame('some\package\subpackage\view\thecontroller\theaction', $name);
                }
                return null;
            });

        self::assertSame('Some\Custom\View\Object\Name', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewReturnsViewResolvedByResolveViewObjectName(): void
    {
        $this->mockObjectManager->expects($this->atLeastOnce())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->willReturn((SimpleTemplateView::class));
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView'));
    }

    /**
     * @test
     */
    public function resolveViewReturnsDefaultViewIfNoViewObjectNameCouldBeResolved(): void
    {
        $this->mockObjectManager->method('getCaseSensitiveObjectName')->willReturn((null));
        $this->actionController->_set('defaultViewObjectName', SimpleTemplateView::class);
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView'));
    }

    /**
     * @test
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotCallable(): void
    {
        $this->expectException(Mvc\Exception\NoSuchActionException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getControllerActionName')->willReturn(('nonExisting'));

        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotPublic(): void
    {
        $this->expectException(Mvc\Exception\InvalidActionVisibilityException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getControllerActionName')->willReturn(('initialize'));

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->method('isMethodPublic')->will($this->returnCallBack(function ($className, $methodName) {
            if ($methodName === 'initializeAction') {
                return false;
            } else {
                return true;
            }
        }));

        $this->mockObjectManager->method('get')->willReturnCallBack(function ($classname) use ($mockReflectionService) {
            if ($classname === ReflectionService::class) {
                return $mockReflectionService;
            }

            return $this->createMock($classname);
        });

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestInjectsControllerContextToView(): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod', 'initializeController']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;
        $mockResponse->setContentType('text/plain');
        $this->inject($this->actionController, 'response', $mockResponse);

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->expects($this->once())->method('setControllerContext')->with($this->mockControllerContext);
        $this->actionController->expects($this->once())->method('resolveView')->willReturn(($mockView));
        $this->actionController->expects($this->once())->method('resolveActionMethodName')->willReturn(('someAction'));

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestInjectsSettingsToView(): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $mockSettings = ['foo', 'bar'];
        $this->inject($this->actionController, 'settings', $mockSettings);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockRequest->method('getHttpRequest')->willReturn(($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->expects($this->once())->method('assign')->with('settings', $mockSettings);
        $this->actionController->expects($this->once())->method('resolveView')->willReturn(($mockView));
        $this->actionController->expects($this->once())->method('resolveActionMethodName')->willReturn(('someAction'));

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
    }

    public static function supportedAndRequestedMediaTypes(): array
    {
        return [
            // supported, Accept header, expected
            [['application/json'], '*/*', 'application/json'],
            [['text/html', 'application/json'], 'application/json', 'application/json'],
            [['text/html'], 'text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8', 'text/html'],
            [['application/json', 'application/xml'], 'text/html, application/json;q=0.7, application/xml;q=0.9', 'application/xml'],
        ];
    }

    /**
     * @test
     * @dataProvider supportedAndRequestedMediaTypes
     */
    public function processRequestSetsNegotiatedContentTypeOnResponse($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn($acceptHeader);
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $mockResponse = new Mvc\ActionResponse;
        $this->inject($this->actionController, 'supportedMediaTypes', $supportedMediaTypes);

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
        self::assertSame($expected, $mockResponse->getContentType());
    }

    /**
     * @test
     * @dataProvider supportedAndRequestedMediaTypes
     */
    public function processRequestUsesContentTypeFromActionResponse($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $mockResponse = new Mvc\ActionResponse;
        $mockResponse->setContentType('application/json');
        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
        self::assertSame('application/json', $mockResponse->getContentType());
    }

    /**
     * @test
     * @dataProvider supportedAndRequestedMediaTypes
     */
    public function processRequestUsesContentTypeFromRenderedView($supportedMediaTypes, $acceptHeader, $expected): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'theActionAction', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView']);
        $this->actionController->method('resolveActionMethodName')->willReturn('theActionAction');
        $this->actionController->method('theActionAction')->willReturn(null);

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $mockResponse = new Mvc\ActionResponse;

        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->method('render')->willReturn(new Response(200, ['Content-Type' => 'application/json']));
        $this->actionController->expects($this->once())->method('resolveView')->willReturn(($mockView));

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
        self::assertSame('application/json', $mockResponse->getContentType());
    }

    /**
     * @test
     */
    public function resolveViewThrowsExceptionIfResolvedViewDoesNotImplementViewInterface(): void
    {
        $this->expectException(Mvc\Exception\ViewNotFoundException::class);
        $this->mockObjectManager->method('getCaseSensitiveObjectName')->willReturn((null));
        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $this->actionController->_call('resolveView');
    }

    public static function ignoredValidationArgumentsProvider(): array
    {
        return [
            [false, false],
            [true, true]
        ];
    }

    /**
     * @test
     * @dataProvider ignoredValidationArgumentsProvider
     */
    public function initializeActionMethodValidatorsDoesNotAddValidatorForIgnoredArgumentsWithoutEvaluation($evaluateIgnoredValidationArgument, $setValidatorShouldBeCalled): void
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['getInformationNeededForInitializeActionMethodValidators']);

        $mockArgument = $this->getMockBuilder(Mvc\Controller\Argument::class)->disableOriginalConstructor()->getMock();
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

        $mockValidator = $this->createMock(ValidatorInterface::class);

        $parameterValidators = [
            'node' => $mockValidator
        ];

        $this->actionController->method('getInformationNeededForInitializeActionMethodValidators')->willReturn(([[], [], [], $ignoredValidationArguments]));

        $this->inject($this->actionController, 'actionMethodName', 'showAction');
        $this->inject($this->actionController, 'arguments', $arguments);

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockValidatorResolver = $this->createMock(ValidatorResolver::class);
        $mockValidatorResolver->method('getBaseValidatorConjunction')->willReturn(($this->getMockBuilder(ConjunctionValidator::class)->getMock()));
        $mockValidatorResolver->method('buildMethodArgumentsValidatorConjunctions')->willReturn(($parameterValidators));
        $this->inject($this->actionController, 'validatorResolver', $mockValidatorResolver);

        if ($setValidatorShouldBeCalled) {
            $mockArgument->expects($this->once())->method('setValidator');
        } else {
            $mockArgument->expects($this->never())->method('setValidator');
        }

        $this->actionController->_call('initializeActionMethodValidators');
    }
}
