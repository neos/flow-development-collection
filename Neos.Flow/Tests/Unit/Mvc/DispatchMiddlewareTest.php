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

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Mvc\DispatchMiddleware;
use Neos\Flow\Mvc\Dispatcher;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Security;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test case for the MVC Dispatcher middleware
 */
class DispatchMiddlewareTest extends UnitTestCase
{
    /**
     * @var DispatchMiddleware
     */
    protected $dispatchMiddleware;

    /**
     * @var Security\Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockDispatcher;

    /**
     * @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockActionRequest;

    /**
     * @var ActionRequestFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockActionRequestFactory;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @var PropertyMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPropertyMapper;

    /**
     * @var PropertyMappingConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPropertyMappingConfiguration;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->dispatchMiddleware = new DispatchMiddleware();

        $this->mockPropertyMapper = $this->getMockBuilder(PropertyMapper::class)->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext = $this->getMockBuilder(Security\Context::class)->disableOriginalConstructor()->getMock();

        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->disableOriginalConstructor()->getMock();
        $httpResponse = new Response();
        $this->mockRequestHandler->method('handle')->willReturn($httpResponse);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);
        $this->mockHttpRequest->method('getUploadedFiles')->willReturn([]);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->inject($this->dispatchMiddleware, 'dispatcher', $this->mockDispatcher);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->onlyMethods(['prepareActionRequest'])->getMock();
        $this->mockActionRequestFactory->method('prepareActionRequest')->willReturn($this->mockActionRequest);

        $this->inject($this->dispatchMiddleware, 'actionRequestFactory', $this->mockActionRequestFactory);
    }

    /**
     * @test
     */
    public function handleDispatchesTheRequest()
    {
        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockDispatcher->expects(self::once())->method('dispatch')->with($this->mockActionRequest);

        $response = $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
        self::assertInstanceOf(ResponseInterface::class, $response);
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

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn(['__internalArgument3' => 'routing']);

        $this->mockActionRequest->expects(self::once())->method('setArguments')->with([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'routing'
        ]);

        $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
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
        $this->mockHttpRequest->method('getQueryParams')->willReturn($requestArguments);
        $this->mockHttpRequest->method('getParsedBody')->willReturn($requestBodyArguments);

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($routingMatchResults);

        $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
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

        $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleDoesNotSetDefaultControllerAndActionNameIfTheyAreSetAlready()
    {
        $this->mockPropertyMapper->method('convert')->with('', 'array', new PropertyMappingConfiguration())->willReturn([]);

        $this->mockHttpRequest->method('getQueryParams')->willReturn([]);
        $this->mockHttpRequest->method('getParsedBody')->willReturn([]);
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);

        $this->mockActionRequest->method('getControllerName')->willReturn('SomeController');
        $this->mockActionRequest->method('getControllerActionName')->willReturn('someAction');
        $this->mockActionRequest->expects(self::never())->method('setControllerName');
        $this->mockActionRequest->expects(self::never())->method('setControllerActionName');

        $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
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

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($matchResults);
        $this->mockActionRequest->expects(self::once())->method('setArguments')->with($matchResults);
        $this->mockHttpRequest->method('getAttribute')->willReturnMap([
            [ServerRequestAttributes::ROUTING_RESULTS, $matchResults]
        ]);
        $this->dispatchMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
}
