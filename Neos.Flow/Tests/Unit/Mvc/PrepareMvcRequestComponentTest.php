<?php
namespace Neos\Flow\Tests\Unit\Mvc;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Mvc\PrepareMvcRequestComponent;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class PrepareMvcRequestComponentTest extends UnitTestCase
{
    /**
     * @var PrepareMvcRequestComponent
     */
    protected $prepareMvcRequestComponent;

    /**
     * @var ComponentContext
     */
    protected $mockComponentContext;

    /**
     * @var ServerRequestInterface
     */
    protected $mockHttpRequest;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->prepareMvcRequestComponent = new PrepareMvcRequestComponent();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getQueryParams')->will($this->returnValue([]));
        $this->mockHttpRequest->expects($this->any())->method('getParsedBody')->will($this->returnValue([]));
        $this->mockHttpRequest->expects($this->any())->method('withParsedBody')->willReturn($this->mockHttpRequest);
        $this->mockComponentContext->expects($this->any())->method('getHttpRequest')->willReturn($this->mockHttpRequest);

        $httpResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockComponentContext->expects($this->any())->method('getHttpResponse')->willReturn($httpResponse);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->getMock();
        $this->inject($this->prepareMvcRequestComponent, 'objectManager', $this->mockObjectManager);

        $this->mockSecurityContext = $this->getMockBuilder(Security\Context::class)->getMock();
        $this->inject($this->prepareMvcRequestComponent, 'securityContext', $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function handleMergesInternalArgumentsWithRoutingMatchResults()
    {
        $this->mockHttpRequest->expects($this->any())->method('getArguments')->will($this->returnValue([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'request',
            '__internalArgument3' => 'request'
        ]));
        $this->mockHttpRequest->expects(self::any())->method('getContent')->willReturn('requestBody');
        $this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue([
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'requestBody'
        ]));

        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', ['__internalArgument3' => 'routing']],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'routing'
        ]);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleStoresTheActionRequestInTheComponentContext()
    {
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
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
                'requestArguments' => [
                    'product' => [
                        'property1' => 'request',
                        'property2' => 'request',
                        'property3' => 'request'
                    ]
                ],
                'requestBodyArguments' => ['product' => ['property2' => 'requestBody', 'property3' => 'requestBody']],
                'routingMatchResults' => ['product' => ['property3' => 'routing']],
                'expectedArguments' => [
                    'product' => [
                        'property1' => 'request',
                        'property2' => 'requestBody',
                        'property3' => 'routing'
                    ]
                ]
            ],
            [
                'requestArguments' => [],
                'requestBodyArguments' => ['someObject' => ['someProperty' => 'someValue']],
                'routingMatchResults' => ['someObject' => ['__identity' => 'someIdentifier']],
                'expectedArguments' => [
                    'someObject' => [
                        'someProperty' => 'someValue',
                        '__identity' => 'someIdentifier'
                    ]
                ]
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
        $this->mockPropertyMapper->expects($this->any())->method('convert')->will($this->returnValue($requestBodyArguments));
        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($expectedArguments);
        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($this->mockActionRequest);

        $this->mockComponentContext->expects($this->atLeastOnce())->method('setParameter')->with(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', $routingMatchResults],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsRequestInSecurityContext()
    {
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);
        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($this->mockActionRequest);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsDefaultControllerAndActionNameIfTheyAreNotSetYet()
    {
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(null));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(null));
        $this->mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
        $this->mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDoesNotSetDefaultControllerAndActionNameIfTheyAreSetAlready()
    {
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeController'));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('someAction'));
        $this->mockActionRequest->expects($this->never())->method('setControllerName');
        $this->mockActionRequest->expects($this->never())->method('setControllerActionName');

        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsActionRequestArgumentsIfARouteMatches()
    {
        $this->mockPropertyMapper->expects($this->any())->method('convert')->with('', 'array', $this->mockPropertyMappingConfiguration)->will($this->returnValue([]));

        $matchResults = [
            'product' => ['name' => 'Some product', 'price' => 123.45],
            'toBeOverridden' => 'from route',
            'newValue' => 'new value from route'
        ];

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($matchResults);
        $this->mockComponentContext->expects($this->any())->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', $matchResults],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);
        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

}
