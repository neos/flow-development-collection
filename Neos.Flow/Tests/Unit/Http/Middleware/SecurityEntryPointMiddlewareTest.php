<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http\Middleware;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Http\Middleware\SecurityEntryPointMiddleware;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Security\Authentication\EntryPointInterface;
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Test case for the SecurityEntryPointMiddleware
 */
final class SecurityEntryPointMiddlewareTest extends UnitTestCase
{
    /**
     * @var SecurityEntryPointMiddleware
     */
    private $securityEntryPointMiddleware;

    /**
     * @var Context|MockObject
     */
    private $mockSecurityContext;

    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest;

    /**
     * @var RequestHandlerInterface|MockObject
     */
    private $mockRequestHandler;

    /**
     * @var AuthenticationRequiredException
     */
    private $mockAuthenticationRequiredException;

    /**
     * @var ActionRequest|MockObject
     */
    private $mockActionRequest;

    /**
     * @var TokenInterface|MockObject
     */
    private $mockTokenWithEntryPoint;

    protected function setUp(): void
    {
        $this->securityEntryPointMiddleware = new SecurityEntryPointMiddleware();

        $this->mockSecurityContext = $this->createMock(Context::class);
        $this->inject($this->securityEntryPointMiddleware, 'securityContext', $this->mockSecurityContext);

        $mockSecurityLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->securityEntryPointMiddleware, 'securityLogger', $mockSecurityLogger);

        $this->buildMockHttpRequest();
        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);

        $this->mockActionRequest = $this->createMock(ActionRequest::class);
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->mockActionRequest);

        $mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->disableOriginalConstructor()->onlyMethods(['prepareActionRequest'])->getMock();
        $mockActionRequestFactory->method('prepareActionRequest')->willReturn($this->mockActionRequest);

        $this->inject($this->securityEntryPointMiddleware, 'actionRequestFactory', $mockActionRequestFactory);

        $this->mockAuthenticationRequiredException = (new AuthenticationRequiredException())->attachInterceptedRequest($this->mockActionRequest);
        $this->mockRequestHandler->method('handle')->willthrowException($this->mockAuthenticationRequiredException);

        $this->mockTokenWithEntryPoint = $this->createMock(TokenInterface::class);
        $this->mockTokenWithEntryPoint->method('getAuthenticationEntryPoint')->willReturn($this->createMock(EntryPointInterface::class));
    }

    protected function buildMockHttpRequest($queryParams = [], $parsedBody = [])
    {
        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpRequest->method('withAttribute')->willReturn($this->mockHttpRequest);
        $this->mockHttpRequest->method('getQueryParams')->willReturn($queryParams);
        $this->mockHttpRequest->method('getParsedBody')->willReturn($parsedBody);
        $this->mockHttpRequest->method('getUploadedFiles')->willReturn([]);
    }

    #[Test]
    public function processReturnsIfNoAuthenticationExceptionWasSet(): void
    {
        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->mockSecurityContext->expects($this->never())->method('getAuthenticationTokens');
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processRethrowsAuthenticationRequiredExceptionIfSecurityContextDoesNotContainAnyAuthenticationToken(): void
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([]);

        $this->expectExceptionObject($this->mockAuthenticationRequiredException);
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processCallsStartAuthenticationOnAllActiveEntryPoints(): void
    {
        $mockAuthenticationToken1 = $this->createMockTokenWithEntryPoint();
        $mockAuthenticationToken2 = $this->createMockTokenWithEntryPoint();
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        /** @var EntryPointInterface|MockObject $mockEntryPoint1 */
        $mockEntryPoint1 = $mockAuthenticationToken1->getAuthenticationEntryPoint();
        $mockEntryPoint1->expects($this->once())->method('startAuthentication')->with($this->mockHttpRequest, self::isInstanceOf(ResponseInterface::class))->willReturn($this->createStub(ResponseInterface::class));

        /** @var EntryPointInterface|MockObject $mockEntryPoint2 */
        $mockEntryPoint2 = $mockAuthenticationToken2->getAuthenticationEntryPoint();
        $mockEntryPoint2->expects($this->once())->method('startAuthentication')->with($this->mockHttpRequest, self::isInstanceOf(ResponseInterface::class))->willReturn($this->createStub(ResponseInterface::class));

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processAllowsAllEntryPointsToModifyTheHttpResponse(): void
    {
        $mockAuthenticationToken1 = $this->createMockTokenWithEntryPoint();
        $mockAuthenticationToken2 = $this->createMockTokenWithEntryPoint();
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        /** @var EntryPointInterface|MockObject $mockEntryPoint1 */
        $mockEntryPoint1 = $mockAuthenticationToken1->getAuthenticationEntryPoint();
        $mockEntryPoint1->method('startAuthentication')->willReturnCallback(static function (ServerRequestInterface $_, ResponseInterface $response) {
            return $response->withAddedHeader('X-Entry-Point', '1');
        });
        /** @var EntryPointInterface|MockObject $mockEntryPoint2 */
        $mockEntryPoint2 = $mockAuthenticationToken2->getAuthenticationEntryPoint();
        $mockEntryPoint2->method('startAuthentication')->willReturnCallback(static function (ServerRequestInterface $_, ResponseInterface $response) {
            return $response->withAddedHeader('X-Entry-Point', '2');
        });

        $entryPointResponse = $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
        self::assertEquals(['1', '2'], $entryPointResponse->getHeader('X-Entry-Point'));
    }

    /**
     * @return TokenInterface|MockObject
     */
    private function createMockTokenWithEntryPoint(): MockObject
    {
        $mockAuthenticationToken = $this->createMock(TokenInterface::class);
        $mockAuthenticationToken->method('getAuthenticationEntryPoint')->willReturn($this->createMock(EntryPointInterface::class));
        return $mockAuthenticationToken;
    }

    /**
     * Note: This test only exists to make sure the security context works inside this middleware as of now.
     * Can be removed once the SecurityContext no longer depends on the ActionRequest
     */
    #[Test]
    public function processSetsSecurityContextRequest(): void
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects($this->once())->method('setRequest')->with($this->mockActionRequest);

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processSetsInterceptedRequestIfSecurityContextContainsAuthenticationTokensWithEntryPoints(): void
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('setInterceptedRequest')->with($this->mockActionRequest);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processDoesNotSetInterceptedRequestIfRequestMethodIsNotGET(): void
    {
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects($this->never())->method('setInterceptedRequest');

        $this->mockHttpRequest->method('getMethod')->willReturn('POST');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processDoesNotSetInterceptedRequestIfAllAuthenticatedTokensAreSessionless(): void
    {
        $mockAuthenticationToken1 = $this->createMock(TestingToken::class);
        $mockEntryPoint1 = $this->createStub(EntryPointInterface::class);
        $mockAuthenticationToken1->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint1);

        $mockAuthenticationToken2 = $this->createMock(TestingToken::class);
        $mockEntryPoint2 = $this->createStub(EntryPointInterface::class);
        $mockAuthenticationToken2->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint2);

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->mockSecurityContext->expects($this->never())->method('setInterceptedRequest');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }


    /**
     * NOTE: The following tests were moved here from DispatchMiddlewareTest, because this middleware currently builds the ActionRequest.
     * Make sure to move them again, once the ActionRequest is built in the DispatchMiddleware again, where it belongs.
     */
    #[Test]
    public function processMergesInternalArgumentsWithRoutingMatchResults()
    {
        $this->buildMockHttpRequest([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'request',
            '__internalArgument3' => 'request'
        ], [
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'requestBody'
        ]);

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn(['__internalArgument3' => 'routing']);

        $this->mockActionRequest->expects($this->once())->method('setArguments')->with([
            '__internalArgument1' => 'request',
            '__internalArgument2' => 'requestBody',
            '__internalArgument3' => 'routing'
        ]);

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function processMergesArgumentsWithRoutingMatchResultsDataProvider(): \Iterator
    {
        yield [
            'requestArguments' => [],
            'requestBodyArguments' => [],
            'routingMatchResults' => null,
            'expectedArguments' => []
        ];
        yield [
            'requestArguments' => [],
            'requestBodyArguments' => ['bodyArgument' => 'foo'],
            'routingMatchResults' => null,
            'expectedArguments' => ['bodyArgument' => 'foo']
        ];
        yield [
            'requestArguments' => ['requestArgument' => 'bar'],
            'requestBodyArguments' => ['bodyArgument' => 'foo'],
            'routingMatchResults' => null,
            'expectedArguments' => ['bodyArgument' => 'foo', 'requestArgument' => 'bar']
        ];
        yield [
            'requestArguments' => ['someArgument' => 'foo'],
            'requestBodyArguments' => ['someArgument' => 'overridden'],
            'routingMatchResults' => [],
            'expectedArguments' => ['someArgument' => 'overridden']
        ];
        yield [
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
        ];
        yield [
            'requestArguments' => [],
            'requestBodyArguments' => ['someObject' => ['someProperty' => 'someValue']],
            'routingMatchResults' => ['someObject' => ['__identity' => 'someIdentifier']],
            'expectedArguments' => [
                'someObject' => [
                    'someProperty' => 'someValue',
                    '__identity' => 'someIdentifier'
                ]
            ]
        ];
    }

    #[DataProvider('processMergesArgumentsWithRoutingMatchResultsDataProvider')]
    #[Test]
    public function processMergesArgumentsWithRoutingMatchResults(array $requestArguments, array $requestBodyArguments, ?array $routingMatchResults, array $expectedArguments)
    {
        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($expectedArguments);
        $this->buildMockHttpRequest($requestArguments, $requestBodyArguments);

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($routingMatchResults);

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processSetsDefaultControllerAndActionNameIfTheyAreNotSetYet()
    {
        $this->mockActionRequest->expects($this->once())->method('getControllerName')->willReturn('');
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->willReturn('');
        $this->mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
        $this->mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    #[Test]
    public function processDoesNotSetDefaultControllerAndActionNameIfTheyAreSetAlready()
    {
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);

        $this->mockActionRequest->method('getControllerName')->willReturn('SomeController');
        $this->mockActionRequest->method('getControllerActionName')->willReturn('someAction');
        $this->mockActionRequest->expects($this->never())->method('setControllerName');
        $this->mockActionRequest->expects($this->never())->method('setControllerActionName');

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
    #[Test]
    public function processSetsActionRequestArgumentsIfARouteMatches()
    {
        $this->mockHttpRequest->method('withParsedBody')->willReturn($this->mockHttpRequest);

        $matchResults = [
            'product' => ['name' => 'Some product', 'price' => 123.45],
            'toBeOverridden' => 'from route',
            'newValue' => 'new value from route'
        ];

        $this->mockHttpRequest->method('getAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($matchResults);
        $this->mockActionRequest->expects($this->once())->method('setArguments')->with($matchResults);

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $this->mockRequestHandler->method('handle')->willReturn($this->createStub(ResponseInterface::class));
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
}
