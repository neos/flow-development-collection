<?php
namespace Neos\Flow\Tests\Unit\Http\Component;

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
use Neos\Flow\Http\Component\SecurityEntryPointComponent;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\DispatchComponent;
use Neos\Flow\Security\Authentication\EntryPointInterface;
use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Test case for the SecurityEntryPointComponent
 */
class SecurityEntryPointComponentTest extends UnitTestCase
{
    /**
     * @var SecurityEntryPointComponent
     */
    private $securityEntryPointComponent;

    /**
     * @var Context|MockObject
     */
    private $mockSecurityContext;

    /**
     * @var ComponentContext|MockObject
     */
    private $mockComponentContext;

    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest;

    /**
     * @var ResponseInterface|MockObject
     */
    private $mockHttpResponse;

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
        $this->securityEntryPointComponent = new SecurityEntryPointComponent();

        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->securityEntryPointComponent, 'securityContext', $this->mockSecurityContext);

        $mockSecurityLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->inject($this->securityEntryPointComponent, 'securityLogger', $mockSecurityLogger);

        $this->mockComponentContext = $this->getMockBuilder(ComponentContext::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $this->mockComponentContext->method('getHttpRequest')->willReturn($this->mockHttpRequest);
        $this->mockHttpResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockComponentContext->method('getHttpResponse')->willReturn($this->mockHttpResponse);
        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockActionRequest->method('getMainRequest')->willReturn($this->mockActionRequest);
        $this->mockComponentContext->method('getParameter')->willReturnCallback(function ($componentClassName, $parameterName) {
            if ($componentClassName === SecurityEntryPointComponent::class && $parameterName === SecurityEntryPointComponent::AUTHENTICATION_EXCEPTION) {
                return $this->mockAuthenticationRequiredException;
            }
            if ($componentClassName === DispatchComponent::class && $parameterName === 'actionRequest') {
                return $this->mockActionRequest;
            }
            return null;
        });

        $this->mockAuthenticationRequiredException = new AuthenticationRequiredException();

        $this->mockTokenWithEntryPoint = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockEntryPoint = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $this->mockTokenWithEntryPoint->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint);
    }

    /**
     * @test
     */
    public function handleReturnsIfNoAuthenticationExceptionWasSet(): void
    {
        $this->mockAuthenticationRequiredException = null;
        $this->mockSecurityContext->expects(self::never())->method('getAuthenticationTokens');
        $this->securityEntryPointComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleRethrowsAuthenticationRequiredExceptionIfSecurityContextDoesNotContainAnyAuthenticationToken(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([]);

        $this->expectExceptionObject($this->mockAuthenticationRequiredException);
        $this->securityEntryPointComponent->handle($this->mockComponentContext);
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
        $mockEntryPoint1->expects(self::once())->method('startAuthentication')->with($this->mockHttpRequest, $this->mockHttpResponse)->willReturn($this->mockHttpResponse);

        /** @var EntryPointInterface|MockObject $mockEntryPoint2 */
        $mockEntryPoint2 = $mockAuthenticationToken2->getAuthenticationEntryPoint();
        $mockEntryPoint2->expects(self::once())->method('startAuthentication')->with($this->mockHttpRequest, $this->mockHttpResponse)->willReturn($this->mockHttpResponse);

        $this->securityEntryPointComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleAllowsAllEntryPointsToModifyTheHttpResponse(): void
    {
        $mockAuthenticationToken1 = $this->createMockTokenWithEntryPoint();
        $mockAuthenticationToken2 = $this->createMockTokenWithEntryPoint();
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        $mockHttpResponse1 = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $this->mockHttpResponse->expects(self::once())->method('withAddedHeader')->with('X-Entry-Point', '1')->willReturn($mockHttpResponse1);
        $mockHttpResponse2 = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $mockHttpResponse1->expects(self::once())->method('withAddedHeader')->with('X-Entry-Point', '2')->willReturn($mockHttpResponse2);

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

        $this->mockComponentContext->expects(self::once())->method('replaceHttpResponse')->with($mockHttpResponse2);

        $this->securityEntryPointComponent->handle($this->mockComponentContext);
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
     * @test
     */
    public function handleSetsInterceptedRequestIfSecurityContextContainsAuthenticationTokensWithEntryPoints(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('setInterceptedRequest')->with($this->mockActionRequest);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->securityEntryPointComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDoesNotSetInterceptedRequestIfRequestMethodIsNotGET(): void
    {
        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$this->mockTokenWithEntryPoint]);
        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest');

        $this->mockHttpRequest->method('getMethod')->willReturn('POST');

        $this->securityEntryPointComponent->handle($this->mockComponentContext);
    }

    /**
     * @test
     */
    public function handleDoesNotSetInterceptedRequestIfAllAuthenticatedTokensAreSessionless(): void
    {
        $mockAuthenticationToken1 = $this->getMockBuilder([TokenInterface::class, SessionlessTokenInterface::class])->getMock();
        $mockEntryPoint1 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken1->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint1);

        $mockAuthenticationToken2 = $this->getMockBuilder([TokenInterface::class, SessionlessTokenInterface::class])->getMock();
        $mockEntryPoint2 = $this->getMockBuilder(EntryPointInterface::class)->getMock();
        $mockAuthenticationToken2->method('getAuthenticationEntryPoint')->willReturn($mockEntryPoint2);

        $this->mockSecurityContext->expects(self::atLeastOnce())->method('getAuthenticationTokens')->willReturn([$mockAuthenticationToken1, $mockAuthenticationToken2]);

        $this->mockHttpRequest->method('getMethod')->willReturn('GET');

        $this->mockSecurityContext->expects(self::never())->method('setInterceptedRequest');

        $this->securityEntryPointComponent->handle($this->mockComponentContext);
    }
}
