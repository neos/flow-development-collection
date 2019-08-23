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
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the Http Component Context
 */
class ComponentContextTest extends UnitTestCase
{
    /**
     * @var ComponentContext
     */
    protected $componentContext;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ResponseInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpResponse;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();

        $this->componentContext = new ComponentContext($this->mockHttpRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function getHttpRequestReturnsTheCurrentRequest()
    {
        self::assertSame($this->mockHttpRequest, $this->componentContext->getHttpRequest());
    }

    /**
     * @test
     */
    public function replaceHttpRequestReplacesTheCurrentRequest()
    {
        /** @var ServerRequestInterface $mockNewHttpRequest */
        $mockNewHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->componentContext->replaceHttpRequest($mockNewHttpRequest);
        self::assertSame($mockNewHttpRequest, $this->componentContext->getHttpRequest());
    }

    /**
     * @test
     */
    public function getHttpResponseReturnsTheCurrentResponse()
    {
        self::assertSame($this->mockHttpResponse, $this->componentContext->getHttpResponse());
    }

    /**
     * @test
     */
    public function replaceHttpResponseReplacesTheCurrentResponse()
    {
        /** @var ResponseInterface $mockNewHttpResponse */
        $mockNewHttpResponse = $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock();
        $this->componentContext->replaceHttpResponse($mockNewHttpResponse);
        self::assertSame($mockNewHttpResponse, $this->componentContext->getHttpResponse());
    }


    /**
     * @test
     */
    public function getParameterReturnsNullIfTheSpecifiedParameterIsNotDefined()
    {
        self::assertNull($this->componentContext->getParameter('Some\Component\ClassName', 'nonExistingParameter'));
    }

    /**
     * @test
     */
    public function setParameterStoresTheGivenParameter()
    {
        $this->componentContext->setParameter('Some\Component\ClassName', 'someParameter', 'someParameterValue');
        self::assertSame('someParameterValue', $this->componentContext->getParameter('Some\Component\ClassName', 'someParameter'));
    }
}
