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
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['dummy']);

        $this->mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockRequest->expects(self::any())->method('getControllerPackageKey')->will(self::returnValue('Some.Package'));
        $this->mockRequest->expects(self::any())->method('getControllerSubpackageKey')->will(self::returnValue('Subpackage'));
        $this->mockRequest->expects(self::any())->method('getFormat')->will(self::returnValue('theFormat'));
        $this->mockRequest->expects(self::any())->method('getControllerName')->will(self::returnValue('TheController'));
        $this->mockRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue('theAction'));
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
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithFormatSuffixIfItExists()
    {
        $this->mockObjectManager->expects(self::once())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will(self::returnValue('ResolvedObjectName'));

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithoutFormatSuffixIfItExists()
    {
        $this->mockObjectManager->expects(self::exactly(2))->method('getCaseSensitiveObjectName')
            ->withConsecutive(
                ['some\package\subpackage\view\thecontroller\theactiontheformat'],
                ['some\package\subpackage\view\thecontroller\theaction']
            )->willReturnOnConsecutiveCalls(null, 'ResolvedObjectName');

        self::assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameRespectsViewFormatToObjectNameMap()
    {
        $this->actionController->_set('viewFormatToObjectNameMap', ['html' => 'Foo', 'theFormat' => 'Some\Custom\View\Object\Name']);
        $this->mockObjectManager->expects(self::exactly(2))->method('getCaseSensitiveObjectName')
            ->withConsecutive(
                ['some\package\subpackage\view\thecontroller\theactiontheformat'],
                ['some\package\subpackage\view\thecontroller\theaction']
            )->willReturn(null);

        self::assertSame('Some\Custom\View\Object\Name', $this->actionController->_call('resolveViewObjectName', $this->mockRequest));
    }

    /**
     * @test
     */
    public function resolveViewReturnsViewResolvedByResolveViewObjectName()
    {
        $this->mockObjectManager->expects(self::atLeastOnce())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will(self::returnValue(SimpleTemplateView::class));
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView', $this->mockRequest));
    }

    /**
     * @test
     */
    public function resolveViewReturnsDefaultViewIfNoViewObjectNameCouldBeResolved()
    {
        $this->mockObjectManager->expects(self::any())->method('getCaseSensitiveObjectName')->will(self::returnValue(null));
        $this->actionController->_set('defaultViewObjectName', SimpleTemplateView::class);
        self::assertInstanceOf(SimpleTemplateView::class, $this->actionController->_call('resolveView', $this->mockRequest));
    }

    /**
     * @test
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotCallable()
    {
        $this->expectException(Mvc\Exception\NoSuchActionException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue('nonExisting'));

        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotPublic()
    {
        $this->expectException(Mvc\Exception\InvalidActionVisibilityException::class);
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
        $this->inject($this->actionController, 'arguments', new Arguments([]));

        $mockRequest = $this->getMockBuilder(Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue('initialize'));

        $mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects(self::any())->method('isMethodPublic')->will(self::returnCallBack(function ($className, $methodName) {
            if ($methodName === 'initializeAction') {
                return false;
            } else {
                return true;
            }
        }));

        $this->mockObjectManager->expects(self::any())->method('get')->will(self::returnCallBack(function ($classname) use ($mockReflectionService) {
            if ($classname === ReflectionService::class) {
                self::returnValue($mockReflectionService);
            }

            return $this->createMock($classname);
        }));

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestInjectsControllerContextToView()
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
        $this->mockRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($mockHttpRequest));

        $mockResponse = new Mvc\ActionResponse;
        $this->inject($this->actionController, 'response', $mockResponse);

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->expects(self::once())->method('setControllerContext')->with($this->mockControllerContext);
        $this->actionController->expects(self::once())->method('resolveView')->with($this->mockRequest)->will(self::returnValue($mockView));
        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn(new Response());
        $this->actionController->expects(self::once())->method('resolveActionMethodName')->with($this->mockRequest)->will(self::returnValue('someAction'));

        $this->actionController->processRequest($this->mockRequest);
    }

    /**
     * @test
     */
    public function processRequestInjectsSettingsToView()
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
        $this->mockRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($mockHttpRequest));

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->expects(self::once())->method('assign')->with('settings', $mockSettings);
        $this->actionController->expects(self::once())->method('resolveView')->with($this->mockRequest)->will(self::returnValue($mockView));
        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn(new Response());
        $this->actionController->expects(self::once())->method('resolveActionMethodName')->with($this->mockRequest)->will(self::returnValue('someAction'));
        $this->actionController->processRequest($this->mockRequest);
    }

    public function supportedAndRequestedMediaTypes()
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
    public function processRequestSetsNegotiatedContentTypeOnResponse($supportedMediaTypes, $acceptHeader, $expected)
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn($acceptHeader);
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn(new Response());
        $this->inject($this->actionController, 'supportedMediaTypes', $supportedMediaTypes);

        $response = $this->actionController->processRequest($this->mockRequest);
        self::assertSame($expected, $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     * @dataProvider supportedAndRequestedMediaTypes
     */
    public function processRequestUsesContentTypeFromActionResponse($supportedMediaTypes, $acceptHeader, $expected)
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod']);
        $this->actionController->method('resolveActionMethodName')->willReturn('indexAction');
        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockResponse = new Response();
        $mockResponse = $mockResponse->withHeader('Content-Type', 'application/json');
        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $this->actionController->expects(self::once())->method('callActionMethod')->willReturn($mockResponse);


        $mockMvcPropertyMappingConfigurationService = $this->createMock(Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->method('getHeaderLine')->with('Accept')->willReturn('application/xml');
        $this->mockRequest->method('getHttpRequest')->willReturn($mockHttpRequest);

        $response = $this->actionController->processRequest($this->mockRequest);
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     * @dataProvider supportedAndRequestedMediaTypes
     */
    public function processRequestUsesContentTypeFromRenderedView($supportedMediaTypes, $acceptHeader, $expected)
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

        $this->inject($this->actionController, 'supportedMediaTypes', ['application/xml']);

        $mockView = $this->createMock(Mvc\View\ViewInterface::class);
        $mockView->method('render')->willReturn(new Response(200, ['Content-Type' => 'application/json']));
        $this->actionController->expects(self::once())->method('resolveView')->with($this->mockRequest)->willReturn($mockView);

        $mockResponse = $this->actionController->processRequest($this->mockRequest);
        self::assertSame('application/json', $mockResponse->getHeaderLine('Content-Type'));
    }

    /**
     * @test
     */
    public function resolveViewThrowsExceptionIfResolvedViewDoesNotImplementViewInterface()
    {
        $this->expectException(Mvc\Exception\ViewNotFoundException::class);
        $this->mockObjectManager->expects(self::any())->method('getCaseSensitiveObjectName')->will(self::returnValue(null));
        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $this->actionController->_call('resolveView', $this->mockRequest);
    }

    public function ignoredValidationArgumentsProvider()
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
    public function initializeActionMethodValidatorsDoesNotAddValidatorForIgnoredArgumentsWithoutEvaluation($evaluateIgnoredValidationArgument, $setValidatorShouldBeCalled)
    {
        $this->actionController = $this->getAccessibleMock(ActionController::class, ['getInformationNeededForInitializeActionMethodValidators']);

        $mockArgument = $this->getMockBuilder(Mvc\Controller\Argument::class)->disableOriginalConstructor()->getMock();
        $mockArgument->expects(self::any())->method('getName')->will(self::returnValue('node'));
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

        $this->actionController->expects(self::any())->method('getInformationNeededForInitializeActionMethodValidators')->will(self::returnValue([[], [], [], $ignoredValidationArguments]));

        $this->inject($this->actionController, 'actionMethodName', 'showAction');

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockValidatorResolver = $this->createMock(ValidatorResolver::class);
        $mockValidatorResolver->expects(self::any())->method('getBaseValidatorConjunction')->will(self::returnValue($this->getMockBuilder(ConjunctionValidator::class)->getMock()));
        $mockValidatorResolver->expects(self::any())->method('buildMethodArgumentsValidatorConjunctions')->will(self::returnValue($parameterValidators));
        $this->inject($this->actionController, 'validatorResolver', $mockValidatorResolver);

        if ($setValidatorShouldBeCalled) {
            $mockArgument->expects(self::once())->method('setValidator');
        } else {
            $mockArgument->expects(self::never())->method('setValidator');
        }

        $this->actionController->_call('initializeActionMethodValidators', $arguments);
    }
}
