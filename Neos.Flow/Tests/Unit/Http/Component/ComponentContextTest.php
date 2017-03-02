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

use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Component Context
 */
class ComponentContextTest extends UnitTestCase
{
    /**
     * @var Http\Component\ComponentContext
     */
    protected $componentContext;

    /**
     * @var Http\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Http\Response|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpResponse;

    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpResponse = $this->getMockBuilder(Http\Response::class)->disableOriginalConstructor()->getMock();

        $this->componentContext = new Http\Component\ComponentContext($this->mockHttpRequest, $this->mockHttpResponse);
    }

    /**
     * @test
     */
    public function getHttpRequestReturnsTheCurrentRequest()
    {
        $this->assertSame($this->mockHttpRequest, $this->componentContext->getHttpRequest());
    }

    /**
     * @test
     */
    public function replaceHttpRequestReplacesTheCurrentRequest()
    {
        /** @var Http\Request $mockNewHttpRequest */
        $mockNewHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->componentContext->replaceHttpRequest($mockNewHttpRequest);
        $this->assertSame($mockNewHttpRequest, $this->componentContext->getHttpRequest());
    }

    /**
     * @test
     */
    public function getHttpResponseReturnsTheCurrentResponse()
    {
        $this->assertSame($this->mockHttpResponse, $this->componentContext->getHttpResponse());
    }

    /**
     * @test
     */
    public function replaceHttpResponseReplacesTheCurrentResponse()
    {
        /** @var Http\Response $mockNewHttpResponse */
        $mockNewHttpResponse = $this->getMockBuilder(Http\Response::class)->disableOriginalConstructor()->getMock();
        $this->componentContext->replaceHttpResponse($mockNewHttpResponse);
        $this->assertSame($mockNewHttpResponse, $this->componentContext->getHttpResponse());
    }


    /**
     * @test
     */
    public function getParameterReturnsNullIfTheSpecifiedParameterIsNotDefined()
    {
        $this->assertNull($this->componentContext->getParameter('Some\Component\ClassName', 'nonExistingParameter'));
    }

    /**
     * @test
     */
    public function setParameterStoresTheGivenParameter()
    {
        $this->componentContext->setParameter('Some\Component\ClassName', 'someParameter', 'someParameterValue');
        $this->assertSame('someParameterValue', $this->componentContext->getParameter('Some\Component\ClassName', 'someParameter'));
    }
}
