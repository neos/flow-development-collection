<?php
namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Neos\Flow\Security;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the MVC Dispatcher Component
 */
class DispatchComponentTest extends UnitTestCase
{
    /**
     * @var DispatchComponent
     */
    protected $dispatchComponent;

    /**
     * @var Security\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var ComponentContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockComponentContext;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpResponse;

    /**
     * @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockDispatcher;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var PropertyMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var PropertyMappingConfiguration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->dispatchComponent = new DispatchComponent();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));

        $this->mockHttpResponse = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->will($this->returnValue($this->mockHttpResponse));

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->dispatchComponent, 'dispatcher', $this->mockDispatcher);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $mockMediaTypeConverter = $this->createMock(MediaTypeConverterInterface::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects($this->any())->method('get')->willReturnMap([
            [ActionRequest::class, $this->mockHttpRequest, $this->mockActionRequest],
            [MediaTypeConverterInterface::class, $mockMediaTypeConverter]
        ]);

        $this->inject($this->dispatchComponent, 'objectManager', $this->mockObjectManager);

        $this->mockSecurityContext = $this->getMockBuilder(Security\Context::class)->getMock();
        $this->inject($this->dispatchComponent, 'securityContext', $this->mockSecurityContext);

        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->dispatchComponent, 'propertyMapper', $this->mockPropertyMapper);
    }

    /**
     * @test
     */
    public function handleSetsRequestInSecurityContext()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));
        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($this->mockActionRequest);

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsDefaultControllerAndActionNameIfTheyAreNotSetYet()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(null));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(null));
        $this->mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
        $this->mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDoesNotSetDefaultControllerAndActionNameIfTheyAreSetAlready()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeController'));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('someAction'));
        $this->mockActionRequest->expects($this->never())->method('setControllerName');
        $this->mockActionRequest->expects($this->never())->method('setControllerActionName');

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsActionRequestArgumentsIfARouteMatches()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $matchResults = [
            'product' => ['name' => 'Some product', 'price' => 123.45],
            'toBeOverridden' => 'from route',
            'newValue' => 'new value from route'
        ];

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($matchResults);
        $this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with(RoutingComponent::class, 'matchResults')->will($this->returnValue($matchResults));
        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDispatchesTheRequest()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with($this->mockActionRequest, $this->mockHttpResponse);

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleStoresTheActionRequestInTheComponentContext()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([]));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @return array
     */
    public function handleMergesArgumentsWithRoutingMatchResultsDataProvider()
    {
        return [
            [
                'requestArguments' => [],
                'requestBodyArguments' => [],
                'routingMatchResults' => null,
                'expectedArguments' => []
            ],
            [
                'requestArguments' => [],
                'requestBodyArguments' => ['bodyArgument' => 'foo'],
                'routingMatchResults' => null,
                'expectedArguments' => ['bodyArgument' => 'foo']
            ],
            [
                'requestArguments' => ['requestArgument' => 'bar'],
                'requestBodyArguments' => ['bodyArgument' => 'foo'],
                'routingMatchResults' => null,
                'expectedArguments' => ['bodyArgument' => 'foo', 'requestArgument' => 'bar']
            ],
            [
                'requestArguments' => ['someArgument' => 'foo'],
                'requestBodyArguments' => ['someArgument' => 'overridden'],
                'routingMatchResults' => [],
                'expectedArguments' => ['someArgument' => 'overridden']
            ],
            [
                'requestArguments' => ['product' => ['property1' => 'request', 'property2' => 'request', 'property3' => 'request']],
                'requestBodyArguments' => ['product' => ['property2' => 'requestBody', 'property3' => 'requestBody']],
                'routingMatchResults' => ['product' => ['property3' => 'routing']],
                'expectedArguments' => ['product' => ['property1' => 'request', 'property2' => 'requestBody', 'property3' => 'routing']]
            ],
            [
                'requestArguments' => [],
                'requestBodyArguments' => ['someObject' => ['someProperty' => 'someValue']],
                'routingMatchResults' => ['someObject' => ['__identity' => 'someIdentifier']],
                'expectedArguments' => ['someObject' => ['someProperty' => 'someValue', '__identity' => 'someIdentifier']]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider handleMergesArgumentsWithRoutingMatchResultsDataProvider()
     */
    public function handleMergesArgumentsWithRoutingMatchResults(array $requestArguments, array $requestBodyArguments, array $routingMatchResults = null, array $expectedArguments)
    {
        $this->mockHttpRequest->expects(self::any())->method('getContent')->willReturn($requestBodyArguments === [] ? '' : $requestBodyArguments);
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue($requestArguments));
        $this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue($requestBodyArguments));
        $this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with(RoutingComponent::class, 'matchResults')->will($this->returnValue($routingMatchResults));

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($expectedArguments);

        $this->dispatchComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleMergesInternalArgumentsWithRoutingMatchResults()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue(['__internalArgument1' => 'request', '__internalArgument2' => 'request', '__internalArgument3' => 'request']));
        $this->mockHttpRequest->expects(self::any())->method('getContent')->willReturn('requestBody');
        $this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue(['__internalArgument2' => 'requestBody', '__internalArgument3' => 'requestBody']));
        $this->mockComponentContext->expects($this->atLeastOnce())->method('getParameter')->with(RoutingComponent::class, 'matchResults')->will($this->returnValue(['__internalArgument3' => 'routing']));

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with(['__internalArgument1' => 'request', '__internalArgument2' => 'requestBody', '__internalArgument3' => 'routing']);

        $this->dispatchComponent->handle($this->mockComponentContext);
    }
}
