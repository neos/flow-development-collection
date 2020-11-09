<?php
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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\ActionRequestFactory;
use Neos\Flow\Security\Authentication\EntryPointInterface;
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Test case for the SecurityEntryPointMiddleware
 */
class SecurityEntryPointMiddlewareTest extends UnitTestCase
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
     * @var ResponseInterface|MockObject
     */
    private $mockHttpResponse;

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

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->securityEntryPointMiddleware, 'securityContext', $this->mockSecurityContext);

        $mockSecurityLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->inject($this->securityEntryPointMiddleware, 'securityLogger', $mockSecurityLogger);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $this->mockHttpRequest->method('withAttribute')->willReturn($this->mockHttpRequest);
        $this->mockHttpResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->mockActionRequest);

        $mockActionRequestFactory = $this->getMockBuilder(ActionRequestFactory::class)->getMock();
        $mockActionRequestFactory->method('createActionRequest')->willReturn($this->mockActionRequest);
        $this->inject($this->securityEntryPointMiddleware, 'actionRequestFactory', $mockActionRequestFactory);

        $this->mockAuthenticationRequiredException = (new AuthenticationRequiredException())->attachInterceptedRequest($this->mockActionRequest);
        $this->mockRequestHandler->method('handle')->willthrowException($this->mockAuthenticationRequiredException);

        $this->mockTokenWithEntryPoint = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockEntryPoint = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $this->mockTokenWithEntryPoint->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint);
    }

    /**
     * @test
     */
    public function handleReturnsIfNoAuthenticationExceptionWasSet(): void
    {
        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockRequestHandler->method('handle')->willReturn($this->mockHttpResponse);
        $this->mockSecurityContext->expects(self::never())->method('getAuthenticationTokens');
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleRethrowsAuthenticationRequiredExceptionIfSecurityContextDoesNotContainAnyAuthenticationToken(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([]);

        $this->expectExceptionObject($this->mockAuthenticationRequiredException);
        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleCallsStartAuthenticationOnAllActiveEntryPoints(): void
    {
        $mockAuthenticationToken1 = $this->createMockTokenWithEntryPoint();
        $mockAuthenticationToken2 = $this->createMockTokenWithEntryPoint();
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        /** @var EntryPointInterface|MockObject $mockEntryPoint1 */
        $mockEntryPoint1 = $mockAuthenticationToken1->getAuthenticationEntryPoint();
        $mockEntryPoint1->expects(self::once())->method('startAuthentication')->with($this->mockHttpRequest, self::isInstanceOf(ResponseInterface::class))->willReturn($this->mockHttpResponse);

        /** @var EntryPointInterface|MockObject $mockEntryPoint2 */
        $mockEntryPoint2 = $mockAuthenticationToken2->getAuthenticationEntryPoint();
        $mockEntryPoint2->expects(self::once())->method('startAuthentication')->with($this->mockHttpRequest, self::isInstanceOf(ResponseInterface::class))->willReturn($this->mockHttpResponse);

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleAllowsAllEntryPointsToModifyTheHttpResponse(): void
    {
        $mockAuthenticationToken1 = $this->createMockTokenWithEntryPoint();
        $mockAuthenticationToken2 = $this->createMockTokenWithEntryPoint();
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

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
        $mockAuthenticationToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockEntryPoint = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint);
        return $mockAuthenticationToken;
    }

    /**
     * Note: This test only exists to make sure the security context works inside this middleware as of now.
     * Can be removed once the SecurityContext no longer depends on the ActionRequest
     * @test
     */
    public function handleSetsSecurityContextRequest(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects(self::once())->method('setRequest')->with($this->mockActionRequest);

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleSetsInterceptedRequestIfSecurityContextContainsAuthenticationTokensWithEntryPoints(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('setInterceptedRequest')->with($this->mockActionRequest);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleDoesNotSetInterceptedRequestIfRequestMethodIsNotGET(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest');

        $this->mockHttpRequest->method('getMethod')->willReturn('POST');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }

    /**
     * @test
     */
    public function handleDoesNotSetInterceptedRequestIfAllAuthenticatedTokensAreSessionless(): void
    {
        $mockAuthenticationToken1 = $this->getMockBuilder(TestingToken::class)->getMock();
        $mockEntryPoint1 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken1->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint1);

        $mockAuthenticationToken2 = $this->getMockBuilder(TestingToken::class)->getMock();
        $mockEntryPoint2 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken2->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint2);

        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest');

        $this->securityEntryPointMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
}
