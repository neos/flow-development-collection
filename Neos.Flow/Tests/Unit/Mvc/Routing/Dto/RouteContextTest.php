<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Request;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the RouteContext DTO
 */
class RouteContextTest extends UnitTestCase
{
    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpRequest1;

    /**
     * @var UriInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockUri1;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockHttpRequest2;

    /**
     * @var UriInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockUri2;

    public function setUp()
    {
        $this->mockHttpRequest1 = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->mockUri1 = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockHttpRequest1->expects($this->any())->method('getUri')->will($this->returnValue($this->mockUri1));

        $this->mockHttpRequest2 = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->mockUri2 = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockHttpRequest2->expects($this->any())->method('getUri')->will($this->returnValue($this->mockUri2));
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierIsTheSameForSimilarUris()
    {
        $this->mockUri1->expects($this->atLeastOnce())->method('getHost')->will($this->returnValue('host.io'));
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('relative/path'));
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->atLeastOnce())->method('getHost')->will($this->returnValue('host.io'));
        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('relative/path'));
        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewHost()
    {
        $this->mockUri1->expects($this->atLeastOnce())->method('getHost')->will($this->returnValue('host1.io'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->atLeastOnce())->method('getHost')->will($this->returnValue('host2.io'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewRelativePath()
    {
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('relative/path1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getRelativePath')->will($this->returnValue('relative/path2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewRequestMethod()
    {
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('GET'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewScheme()
    {
        $this->mockUri1->expects($this->any())->method('getScheme')->will($this->returnValue('http'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->any())->method('getScheme')->will($this->returnValue('https'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewQuery()
    {
        $this->mockUri1->expects($this->any())->method('getQuery')->will($this->returnValue('query1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->any())->method('getQuery')->will($this->returnValue('query2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewFragment()
    {
        $this->mockUri1->expects($this->any())->method('getFragment')->will($this->returnValue('fragment1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->any())->method('getFragment')->will($this->returnValue('fragment2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewParameters()
    {
        $parameters1 = RouteParameters::createEmpty();
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, $parameters1))->getCacheEntryIdentifier();

        $parameters2 = $parameters1->withParameter('newParameter', 'someValue');
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest1, $parameters2))->getCacheEntryIdentifier();

        $this->assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }
}
