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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Mvc\Controller\Arguments;

/**
 * Testcase for the MVC Action Controller
 */
class ActionControllerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var ActionController
     */
    protected $actionController;

    /**
     * @var \TYPO3\Flow\Mvc\ActionRequest
     */
    protected $mockRequest;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var \TYPO3\Flow\Mvc\ViewConfigurationManager
     */
    protected $mockViewConfigurationManager;

    /**
     * @var \TYPO3\Flow\Mvc\Controller\ControllerContext
     */
    protected $mockControllerContext;

    public function setUp()
    {
        $this->actionController = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\ActionController::class, array('dummy'));

        $this->mockRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockRequest->expects($this->any())->method('getControllerPackageKey')->will($this->returnValue('Some.Package'));
        $this->mockRequest->expects($this->any())->method('getControllerSubpackageKey')->will($this->returnValue('Subpackage'));
        $this->mockRequest->expects($this->any())->method('getFormat')->will($this->returnValue('theFormat'));
        $this->mockRequest->expects($this->any())->method('getControllerName')->will($this->returnValue('TheController'));
        $this->mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('theAction'));
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $this->mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $this->mockControllerContext = $this->getMockBuilder(\TYPO3\Flow\Mvc\Controller\ControllerContext::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $this->mockViewConfigurationManager = $this->createMock(\TYPO3\Flow\Mvc\ViewConfigurationManager::class);
        $this->inject($this->actionController, 'viewConfigurationManager', $this->mockViewConfigurationManager);
    }

    /**
     * @test
     */
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithFormatSuffixIfItExists()
    {
        $this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue('ResolvedObjectName'));

        $this->assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameReturnsObjectNameOfCustomViewWithoutFormatSuffixIfItExists()
    {
        $this->mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theaction')->will($this->returnValue('ResolvedObjectName'));

        $this->assertSame('ResolvedObjectName', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewObjectNameRespectsViewFormatToObjectNameMap()
    {
        $this->actionController->_set('viewFormatToObjectNameMap', array('html' => 'Foo', 'theFormat' => 'Some\Custom\View\Object\Name'));
        $this->mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue(false));
        $this->mockObjectManager->expects($this->at(1))->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theaction')->will($this->returnValue(false));

        $this->assertSame('Some\Custom\View\Object\Name', $this->actionController->_call('resolveViewObjectName'));
    }

    /**
     * @test
     */
    public function resolveViewReturnsViewResolvedByResolveViewObjectName()
    {
        $this->mockObjectManager->expects($this->atLeastOnce())->method('getCaseSensitiveObjectName')->with('some\package\subpackage\view\thecontroller\theactiontheformat')->will($this->returnValue('ResolvedObjectName'));

        $mockView = $this->createMock(\TYPO3\Flow\Mvc\View\ViewInterface::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with('ResolvedObjectName')->will($this->returnValue($mockView));

        $this->assertSame($mockView, $this->actionController->_call('resolveView'));
    }

    /**
     * @test
     */
    public function resolveViewReturnsDefaultViewIfNoViewObjectNameCouldBeResolved()
    {
        $this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $mockView = $this->createMock(\TYPO3\Flow\Mvc\View\ViewInterface::class);
        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue($mockView));

        $this->assertSame($mockView, $this->actionController->_call('resolveView'));
    }

    /**
     * @test
     * @expectedException  \TYPO3\Flow\Mvc\Exception\NoSuchActionException
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotCallable()
    {
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $mockRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('nonExisting'));

        $this->inject($this->actionController, 'arguments', new Arguments(array()));

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
        $mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $mockResponse = $this->createMock(\TYPO3\Flow\Http\Response::class);

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     * @expectedException  \TYPO3\Flow\Mvc\Exception\InvalidActionVisibilityException
     */
    public function processRequestThrowsExceptionIfRequestedActionIsNotPublic()
    {
        $this->actionController = new ActionController();

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
        $this->inject($this->actionController, 'arguments', new Arguments(array()));

        $mockRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue('initialize'));

        $mockReflectionService = $this->getMockBuilder(\TYPO3\Flow\Reflection\ReflectionService::class)->disableOriginalConstructor()->getMock();
        $mockReflectionService->expects($this->any())->method('isMethodPublic')->will($this->returnCallback(function ($className, $methodName) {
            if ($methodName === 'initializeAction') {
                return false;
            } else {
                return true;
            }
        }));

        $this->mockObjectManager->expects($this->any())->method('get')->will($this->returnCallback(function ($classname) use ($mockReflectionService) {
            if ($classname === \TYPO3\Flow\Reflection\ReflectionService::class) {
                $this->returnValue($mockReflectionService);
            }

            return $this->createMock($classname);
        }));

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
        $mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $mockResponse = $this->createMock(\TYPO3\Flow\Http\Response::class);

        $this->actionController->processRequest($mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestInjectsControllerContextToView()
    {
        $this->actionController = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\ActionController::class, array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod', 'initializeController'));

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);
        $this->inject($this->actionController, 'request', $this->mockRequest);

        $this->inject($this->actionController, 'arguments', new Arguments(array()));

        $mockMvcPropertyMappingConfigurationService = $this->createMock(\TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
        $this->mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $mockResponse = $this->createMock(\TYPO3\Flow\Http\Response::class);

        $mockView = $this->createMock(\TYPO3\Flow\Mvc\View\ViewInterface::class);
        $mockView->expects($this->once())->method('setControllerContext')->with($this->mockControllerContext);
        $this->actionController->expects($this->once())->method('resolveView')->will($this->returnValue($mockView));

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
    }

    /**
     * @test
     */
    public function processRequestInjectsSettingsToView()
    {
        $this->actionController = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\ActionController::class, array('resolveActionMethodName', 'initializeActionMethodArguments', 'initializeActionMethodValidators', 'resolveView', 'callActionMethod'));

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);
        $this->inject($this->actionController, 'controllerContext', $this->mockControllerContext);

        $mockSettings = array('foo', 'bar');
        $this->inject($this->actionController, 'settings', $mockSettings);

        $mockMvcPropertyMappingConfigurationService = $this->createMock(\TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService::class);
        $this->inject($this->actionController, 'mvcPropertyMappingConfigurationService', $mockMvcPropertyMappingConfigurationService);

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->any())->method('getNegotiatedMediaType')->will($this->returnValue('*/*'));
        $this->mockRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

        $mockResponse = $this->createMock(\TYPO3\Flow\Http\Response::class);

        $mockView = $this->createMock(\TYPO3\Flow\Mvc\View\ViewInterface::class);
        $mockView->expects($this->once())->method('assign')->with('settings', $mockSettings);
        $this->actionController->expects($this->once())->method('resolveView')->will($this->returnValue($mockView));

        $this->actionController->processRequest($this->mockRequest, $mockResponse);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\ViewNotFoundException
     */
    public function resolveViewThrowsExceptionIfResolvedViewDoesNotImplementViewInterface()
    {
        $this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $invalidView = new \stdClass();
        $this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue($invalidView));

        $this->actionController->_call('resolveView');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\ViewNotFoundException
     */
    public function resolveViewThrowsExceptionIfViewCouldNotBeResolved()
    {
        $this->mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $this->actionController->_set('defaultViewObjectName', 'ViewDefaultObjectName');
        $this->mockObjectManager->expects($this->once())->method('get')->with('ViewDefaultObjectName')->will($this->returnValue(null));

        $this->actionController->_call('resolveView');
    }

    public function ignoredValidationArgumentsProvider()
    {
        return array(
            array(false, false),
            array(true, true)
        );
    }

    /**
     * @test
     * @dataProvider ignoredValidationArgumentsProvider
     */
    public function initializeActionMethodValidatorsDoesNotAddValidatorForIgnoredArgumentsWithoutEvaluation($evaluateIgnoredValidationArgument, $setValidatorShouldBeCalled)
    {
        $this->actionController = $this->getAccessibleMock(\TYPO3\Flow\Mvc\Controller\ActionController::class, array('getInformationNeededForInitializeActionMethodValidators'));

        $mockArgument = $this->getMockBuilder(\TYPO3\Flow\Mvc\Controller\Argument::class)->disableOriginalConstructor()->getMock();
        $mockArgument->expects($this->any())->method('getName')->will($this->returnValue('node'));
        $arguments = new Arguments();
        $arguments['node'] = $mockArgument;

        $ignoredValidationArguments = array(
            'showAction' => array(
                'node' => array(
                    'evaluate' => $evaluateIgnoredValidationArgument
                )
            )
        );

        $mockValidator = $this->createMock(\TYPO3\Flow\Validation\Validator\ValidatorInterface::class);

        $parameterValidators = array(
            'node' => $mockValidator
        );

        $this->actionController->expects($this->any())->method('getInformationNeededForInitializeActionMethodValidators')->will($this->returnValue(array(array(), array(), array(), $ignoredValidationArguments)));

        $this->inject($this->actionController, 'actionMethodName', 'showAction');
        $this->inject($this->actionController, 'arguments', $arguments);

        $this->inject($this->actionController, 'objectManager', $this->mockObjectManager);

        $mockValidatorResolver = $this->createMock(\TYPO3\Flow\Validation\ValidatorResolver::class);
        $mockValidatorResolver->expects($this->any())->method('buildMethodArgumentsValidatorConjunctions')->will($this->returnValue($parameterValidators));
        $this->inject($this->actionController, 'validatorResolver', $mockValidatorResolver);

        if ($setValidatorShouldBeCalled) {
            $mockArgument->expects($this->once())->method('setValidator');
        } else {
            $mockArgument->expects($this->never())->method('setValidator');
        }

        $this->actionController->_call('initializeActionMethodValidators');
    }
}
