<?php
namespace Neos\Flow\Tests\Unit\Mvc;

use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Mvc\PrepareMvcRequestComponent;
use Neos\Flow\Mvc\Routing\RoutingComponent;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Security\Context;
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
     * @var PropertyMapper
     */
    protected $mockPropertyMapper;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var ActionRequestFactory
     */
    protected $mockActionRequestFactory;

    /**
     * @var Context
     */
    protected $mockSecurityContext;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->prepareMvcRequestComponent = new PrepareMvcRequestComponent();

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);
        $this->mockHttpRequest->method('getUploadedFiles')->willReturn([]);
        $this->mockComponentContext->method('getHttpRequest')->willReturn($this->mockHttpRequest);

        $httpResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockComponentContext->method('getHttpResponse')->willReturn($httpResponse);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->setMethods(['prepareActionRequest'])->getMock();
        $this->mockActionRequestFactory->expects(self::any())->method('prepareActionRequest')->willReturn($this->mockActionRequest);

        $this->inject($this->prepareMvcRequestComponent, 'actionRequestFactory', $this->mockActionRequestFactory);

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->getMock();
        $this->inject($this->prepareMvcRequestComponent, 'securityContext', $this->mockSecurityContext);

        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->getMock();
    }

    /**
     * @test
     */
    public function handleMergesInternalArgumentsWithRoutingMatchResults()
    {
        $this->mockHttpRequest->method('getQueryParams')->willReturn([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'request',
            '__internalArgument3' => 'request'
        ]);

        $this->mockHttpRequest->method('getParsedBody')->willReturn([
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'requestBody'
        ]);

        $this->mockComponentContext->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', ['__internalArgument3' => 'routing']],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);

        $this->mockActionRequest->expects(self::once())->method('setArguments')->with([
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
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);
        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);

        $this->mockComponentContext->expects(self::atLeastOnce())->method('setParameter')->with(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);
        $this->mockComponentContext->method('getParameter')->willReturnMap([
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
        $this->mockActionRequest->expects(self::once())->method('setArguments')->with($expectedArguments);
        $this->mockSecurityContext->expects(self::once())->method('setRequest')->with($this->mockActionRequest);
        $this->mockHttpRequest->method('getQueryParams')->willReturn($requestArguments);
        $this->mockHttpRequest->method('getParsedBody')->willReturn($requestBodyArguments);

        $this->mockComponentContext->expects(self::atLeastOnce())->method('setParameter')->with(DispatchComponent::class, 'actionRequest', $this->mockActionRequest);
        $this->mockComponentContext->method('getParameter')->willReturnMap([
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
        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);
        $this->mockComponentContext->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', []],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);
        $this->mockSecurityContext->expects(self::once())->method('setRequest')->with($this->mockActionRequest);

        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleSetsDefaultControllerAndActionNameIfTheyAreNotSetYet()
    {
        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);

        $this->mockActionRequest->expects(self::once())->method('getControllerName')->willReturn('');
        $this->mockActionRequest->expects(self::once())->method('getControllerActionName')->willReturn('');
        $this->mockActionRequest->expects(self::once())->method('setControllerName')->with('Standard');
        $this->mockActionRequest->expects(self::once())->method('setControllerActionName')->with('index');

        $this->mockComponentContext->method('getParameter')->willReturnMap([
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
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);

        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);
        $this->mockHttpRequest->method('getUploadedFiles')->willReturn([]);
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);

        $this->mockActionRequest->method('getControllerName')->willReturn('SomeController');
        $this->mockActionRequest->method('getControllerActionName')->willReturn('someAction');
        $this->mockActionRequest->expects(self::never())->method('setControllerName');
        $this->mockActionRequest->expects(self::never())->method('setControllerActionName');

        $this->mockComponentContext->method('getParameter')->willReturnMap([
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
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);

        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);

        $matchResults = [
            'product' => ['name' => 'Some product', 'price' => 123.45],
            'toBeOverridden' => 'from route',
            'newValue' => 'new value from route'
        ];

        $this->mockActionRequest->expects(self::once())->method('setArguments')->with($matchResults);
        $this->mockComponentContext->method('getParameter')->willReturnMap([
            [RoutingComponent::class, 'matchResults', $matchResults],
            [DispatchComponent::class, 'actionRequest', $this->mockActionRequest]
        ]);
        $this->prepareMvcRequestComponent->handle($this->mockComponentContext);
    }
}
