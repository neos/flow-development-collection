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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the RouteContext DTO
 */
class RouteContextTest extends UnitTestCase
{
    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHttpRequest1;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockUri1;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHttpRequest2;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockUri2;

    protected function setUp(): void
    {
        $this->mockHttpRequest1 = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockUri1 = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockUri1->expects(self::any())->method('withFragment')->willReturn($this->mockUri1);
        $this->mockUri1->expects(self::any())->method('withQuery')->willReturn($this->mockUri1);
        $this->mockUri1->expects(self::any())->method('withPath')->willReturn($this->mockUri1);
        $this->mockHttpRequest1->expects(self::any())->method('getUri')->willReturn($this->mockUri1);

        $this->mockHttpRequest2 = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockUri2 = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockUri2->expects(self::any())->method('withFragment')->willReturn($this->mockUri2);
        $this->mockUri2->expects(self::any())->method('withQuery')->willReturn($this->mockUri2);
        $this->mockUri2->expects(self::any())->method('withPath')->willReturn($this->mockUri2);
        $this->mockHttpRequest2->expects(self::any())->method('getUri')->will(self::returnValue($this->mockUri2));
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierIsTheSameForSimilarUris()
    {
        $this->mockUri1->expects(self::atLeastOnce())->method('getHost')->will(self::returnValue('host.io'));
        $this->mockHttpRequest1->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects(self::atLeastOnce())->method('getHost')->will(self::returnValue('host.io'));
        $this->mockHttpRequest2->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewHost()
    {
        $this->mockUri1->expects(self::atLeastOnce())->method('getHost')->will(self::returnValue('host1.io'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects(self::atLeastOnce())->method('getHost')->will(self::returnValue('host2.io'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewRelativePath()
    {
        $mockUri1 = new Uri('https://localhost/relative/path1');
        $mockUri2 = new Uri('https://localhost/relative/path2');

        $mockHttpRequest1 = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest1->expects(self::any())->method('getUri')->willReturn($mockUri1);
        $mockHttpRequest2 = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockHttpRequest2->expects(self::any())->method('getUri')->willReturn($mockUri2);

        $cacheIdentifier1 = (new RouteContext($mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();
        $cacheIdentifier2 = (new RouteContext($mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierChangesWithNewRequestMethod()
    {
        $this->mockHttpRequest1->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('GET'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockHttpRequest2->expects(self::atLeastOnce())->method('getMethod')->will(self::returnValue('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewScheme()
    {
        $this->mockUri1->expects(self::any())->method('getScheme')->will(self::returnValue('http'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects(self::any())->method('getScheme')->will(self::returnValue('https'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewQuery()
    {
        $this->mockUri1->expects(self::any())->method('getQuery')->will(self::returnValue('query1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects(self::any())->method('getQuery')->will(self::returnValue('query2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    /**
     * @test
     */
    public function getCacheEntryIdentifierDoesNotChangeWithNewFragment()
    {
        $this->mockUri1->expects(self::any())->method('getFragment')->will(self::returnValue('fragment1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects(self::any())->method('getFragment')->will(self::returnValue('fragment2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
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

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }
}
