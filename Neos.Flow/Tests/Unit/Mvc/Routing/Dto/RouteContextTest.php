<?php

declare(strict_types=1);

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
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Testcase for the RouteContext DTO
 */
final class RouteContextTest extends UnitTestCase
{
    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest1;

    /**
     * @var UriInterface|MockObject
     */
    private $mockUri1;

    /**
     * @var ServerRequestInterface|MockObject
     */
    private $mockHttpRequest2;

    /**
     * @var UriInterface|MockObject
     */
    private $mockUri2;

    protected function setUp(): void
    {
        $this->mockHttpRequest1 = $this->createMock(ServerRequestInterface::class);

        $this->mockUri1 = $this->createMock(UriInterface::class);
        $this->mockUri1->method('withFragment')->willReturn($this->mockUri1);
        $this->mockUri1->method('withQuery')->willReturn($this->mockUri1);
        $this->mockUri1->method('withPath')->willReturn($this->mockUri1);
        $this->mockHttpRequest1->method('getUri')->willReturn($this->mockUri1);

        $this->mockHttpRequest2 = $this->createMock(ServerRequestInterface::class);

        $this->mockUri2 = $this->createMock(UriInterface::class);
        $this->mockUri2->method('withFragment')->willReturn($this->mockUri2);
        $this->mockUri2->method('withQuery')->willReturn($this->mockUri2);
        $this->mockUri2->method('withPath')->willReturn($this->mockUri2);
        $this->mockHttpRequest2->method('getUri')->willReturn(($this->mockUri2));
    }

    #[Test]
    public function getCacheEntryIdentifierIsTheSameForSimilarUris()
    {
        $this->mockUri1->expects($this->atLeastOnce())->method('getHost')->willReturn(('host.io'));
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->atLeastOnce())->method('getHost')->willReturn(('host.io'));
        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierChangesWithNewHost()
    {
        $this->mockUri1->expects($this->atLeastOnce())->method('getHost')->willReturn(('host1.io'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->expects($this->atLeastOnce())->method('getHost')->willReturn(('host2.io'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierChangesWithNewRelativePath()
    {
        $mockUri1 = new Uri('https://localhost/relative/path1');
        $mockUri2 = new Uri('https://localhost/relative/path2');

        $mockHttpRequest1 = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest1->method('getUri')->willReturn($mockUri1);
        $mockHttpRequest2 = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest2->method('getUri')->willReturn($mockUri2);

        $cacheIdentifier1 = (new RouteContext($mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();
        $cacheIdentifier2 = (new RouteContext($mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierChangesWithNewRequestMethod()
    {
        $this->mockHttpRequest1->expects($this->atLeastOnce())->method('getMethod')->willReturn(('GET'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockHttpRequest2->expects($this->atLeastOnce())->method('getMethod')->willReturn(('POST'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierDoesNotChangeWithNewScheme()
    {
        $this->mockUri1->method('getScheme')->willReturn(('http'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->method('getScheme')->willReturn(('https'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierDoesNotChangeWithNewQuery()
    {
        $this->mockUri1->method('getQuery')->willReturn(('query1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->method('getQuery')->willReturn(('query2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierDoesNotChangeWithNewFragment()
    {
        $this->mockUri1->method('getFragment')->willReturn(('fragment1'));
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        $this->mockUri2->method('getFragment')->willReturn(('fragment2'));
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest2, RouteParameters::createEmpty()))->getCacheEntryIdentifier();

        self::assertSame($cacheIdentifier1, $cacheIdentifier2);
    }

    #[Test]
    public function getCacheEntryIdentifierChangesWithNewParameters()
    {
        $parameters1 = RouteParameters::createEmpty();
        $cacheIdentifier1 = (new RouteContext($this->mockHttpRequest1, $parameters1))->getCacheEntryIdentifier();

        $parameters2 = $parameters1->withParameter('newParameter', 'someValue');
        $cacheIdentifier2 = (new RouteContext($this->mockHttpRequest1, $parameters2))->getCacheEntryIdentifier();

        self::assertNotSame($cacheIdentifier1, $cacheIdentifier2);
    }
}
