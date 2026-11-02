<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing;

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
use Neos\Cache\CacheAwareInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the Router Caching Service
 *
 */
final class RouterCachingServiceTest extends UnitTestCase
{
    /**
     * @var RouterCachingService
     */
    protected $routerCachingService;

    /**
     * @var VariableFrontend|MockObject
     */
    protected $mockRouteCache;

    /**
     * @var StringFrontend|MockObject
     */
    protected $mockResolveCache;

    /**
     * @var PersistenceManagerInterface|MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var ApplicationContext|MockObject
     */
    protected $mockApplicationContext;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|MockObject
     */
    protected $mockUri;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->routerCachingService = $this->getAccessibleMock(RouterCachingService::class, []);

        $this->mockRouteCache = $this->createMock(VariableFrontend::class);
        $this->inject($this->routerCachingService, 'routeCache', $this->mockRouteCache);

        $this->mockResolveCache = $this->createMock(StringFrontend::class);
        $this->inject($this->routerCachingService, 'resolveCache', $this->mockResolveCache);

        $this->mockPersistenceManager  = $this->createMock(PersistenceManagerInterface::class);
        $this->inject($this->routerCachingService, 'persistenceManager', $this->mockPersistenceManager);

        $mockSystemLogger  = $this->createMock(LoggerInterface::class);
        $this->inject($this->routerCachingService, 'logger', $mockSystemLogger);

        $mockObjectManager  = $this->createMock(ObjectManagerInterface::class);
        $this->mockApplicationContext = $this->createMock(ApplicationContext::class);
        $mockObjectManager->method('getContext')->willReturn(($this->mockApplicationContext));
        $this->inject($this->routerCachingService, 'objectManager', $mockObjectManager);

        $this->inject($this->routerCachingService, 'objectManager', $mockObjectManager);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpRequest->method('getMethod')->willReturn(('GET'));
        $this->mockUri = new Uri('http://subdomain.domain.com/some/route/path');
        $this->mockHttpRequest->method('getUri')->willReturn(($this->mockUri));
    }

    #[Test]
    public function initializeObjectDoesNotFlushCachesInProductionContext()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->willReturn((false));
        $this->mockRouteCache->expects($this->never())->method('get');
        $this->mockRouteCache->expects($this->never())->method('flush');
        $this->mockResolveCache->expects($this->never())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    #[Test]
    public function initializeDoesNotFlushCachesInDevelopmentContextIfRoutingSettingsHaveNotChanged()
    {
        $cachedRoutingSettings = ['Some.Package' => true, 'Some.OtherPackage' => ['position' => 'start', 'suffix' => 'Foo', 'variables' => ['foo' => 'bar']]];

        $actualRoutingSettings = $cachedRoutingSettings;

        $this->inject($this->routerCachingService, 'routingSettings', $actualRoutingSettings);

        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->willReturn((true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->willReturn(($cachedRoutingSettings));

        $this->mockRouteCache->expects($this->never())->method('flush');
        $this->mockResolveCache->expects($this->never())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    #[Test]
    public function initializeFlushesCachesInDevelopmentContextIfRoutingSettingsHaveChanged()
    {
        $cachedRoutingSettings = ['Some.Package' => true, 'Some.OtherPackage' => ['position' => 'start', 'suffix' => 'Foo', 'variables' => ['foo' => 'bar']]];

        $actualRoutingSettings = $cachedRoutingSettings;
        $actualRoutingSettings['Some.OtherPackage']['variables']['foo'] = 'baz';

        $this->inject($this->routerCachingService, 'routingSettings', $actualRoutingSettings);

        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->willReturn((true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->willReturn(($cachedRoutingSettings));

        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    #[Test]
    public function initializeFlushesCachesInDevelopmentContextIfRoutingSettingsWhereNotStoredPreviously()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->willReturn((true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->willReturn((false));

        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * Data provider for containsObjectDetectsObjectsInVariousSituations()
     */
    public static function containsObjectDetectsObjectsInVariousSituationsDataProvider(): \Iterator
    {
        $object = new \stdClass();
        yield [true, $object];
        yield [true, ['foo' => $object]];
        yield [true, ['foo' => 'bar', 'baz' => $object]];
        yield [true, ['foo' => ['bar' => ['baz' => 'quux', 'here' => $object]]]];
        yield [false, 'no object'];
        yield [false, ['foo' => 'no object']];
        yield [false, true];
    }

    #[DataProvider('containsObjectDetectsObjectsInVariousSituationsDataProvider')]
    #[Test]
    public function containsObjectDetectsObjectsInVariousSituations($expectedResult, $subject)
    {
        $actualResult = $this->routerCachingService->_call('containsObject', $subject);
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function getCachedMatchResultsReturnsCachedMatchResultsIfFoundInCache()
    {
        $expectedResult = ['cached' => 'route values'];
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->willReturn(($expectedResult));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function getCachedMatchResultsReturnsFalseIfNotFoundInCache()
    {
        $expectedResult = false;
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->willReturn((false));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function storeMatchResultsDoesNotStoreMatchResultsInCacheIfTheyContainObjects()
    {
        $matchResults = ['this' => ['contains' => ['objects', new \stdClass()]]];

        $this->mockRouteCache->expects($this->never())->method('set');

        $this->routerCachingService->storeMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()), $matchResults);
    }

    #[Test]
    public function storeMatchExtractsUuidsAndTheHashedUriPathToCacheTags()
    {
        $uuid1 = '550e8400-e29b-11d4-a716-446655440000';
        $uuid2 = '302abe9c-7d07-4200-a868-478586019290';
        $matchResults = ['some' => ['matchResults' => ['uuid', $uuid1]], 'foo' => $uuid2];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $this->mockRouteCache->expects($this->once())->method('set')->with($routeContext->getCacheEntryIdentifier(), $matchResults, [$uuid1, $uuid2, md5('some'), md5('some/route'), md5('some/route/path')]);

        $this->routerCachingService->storeMatchResults($routeContext, $matchResults);
    }

    #[Test]
    public function getCachedResolvedUriReturnsCachedResolvedUriConstraintsIfFoundInCache()
    {
        $routeValues = ['b' => 'route values', 'a' => 'Some more values'];

        $expectedResult = UriConstraints::create()->withPath('cached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('get')->willReturn(($expectedResult));

        $actualResult = $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()));
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function storeResolvedUriConstraintsConvertsObjectsToHashesToGenerateCacheIdentifier()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];
        $cacheIdentifier = '868abeec5c300408f418bf198542daec';

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->willReturn(('objectIdentifier'));

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }

    #[Test]
    public function storeResolvedUriConstraintsConvertsObjectsToHashesToGenerateRouteTags()
    {
        $mockUuid = '550e8400-e29b-11d4-a716-446655440000';
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];
        $cacheIdentifier = '368edb26a8347d7f635b872e73a8e5e9';

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->willReturn(($mockUuid));

        $resolvedUriConstraints = UriConstraints::create()->withPath('path');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints, [$mockUuid, md5('path')]);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }

    #[Test]
    public function storeResolvedUriConstraintsExtractsUuidsToCacheTags()
    {
        $uuid1 = '550e8400-e29b-11d4-a716-446655440000';
        $uuid2 = '302abe9c-7d07-4200-a868-478586019290';
        $routeValues = ['some' => ['routeValues' => ['uuid', $uuid1]], 'foo' => $uuid2];
        $resolveContext = new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty());
        $resolvedUriConstraints = UriConstraints::create()->withPath('some/request/path');

        /** @var RouterCachingService|MockObject $routerCachingService */
        $routerCachingService = $this->getAccessibleMock(RouterCachingService::class, ['buildResolveCacheIdentifier']);
        $routerCachingService->expects($this->atLeastOnce())->method('buildResolveCacheIdentifier')->with($resolveContext, $routeValues)->willReturn(('cacheIdentifier'));
        $this->inject($routerCachingService, 'resolveCache', $this->mockResolveCache);

        $this->mockResolveCache->expects($this->once())->method('set')->with('cacheIdentifier', $resolvedUriConstraints, [$uuid1, $uuid2, md5('some'), md5('some/request'), md5('some/request/path')]);

        $routerCachingService->storeResolvedUriConstraints($resolveContext, $resolvedUriConstraints);
    }

    #[Test]
    public function storeResolvedUriConstraintsCreatesSeparateCacheEntriesPerRouteParameters()
    {
        $routeValues = ['foo' => 'bar'];
        $routeParameters1 = RouteParameters::createEmpty()->withParameter('foo', 'bar');
        $routeParameters2 = RouteParameters::createEmpty()->withParameter('foo', 'baz');

        $resolvedUriConstraints = UriConstraints::create()->withPath('path');
        $createdCacheEntries = [];
        $this->mockResolveCache->method('set')->willReturnCallback(static function (string $cacheEntryIdentifier) use (&$createdCacheEntries) {
            $createdCacheEntries[$cacheEntryIdentifier] = true;
        });

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', $routeParameters1), $resolvedUriConstraints);
        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', $routeParameters2), $resolvedUriConstraints);
        self::assertCount(2, $createdCacheEntries);
    }

    #[Test]
    public function getCachedResolvedUriConstraintSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->willReturn((null));

        $this->mockResolveCache->expects($this->never())->method('has');
        $this->mockResolveCache->expects($this->never())->method('set');

        $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()));
    }

    #[Test]
    public function flushCachesResetsBothRoutingCaches()
    {
        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');
        $this->routerCachingService->flushCaches();
    }

    #[Test]
    public function storeResolvedUriConstraintsConvertsObjectsImplementingCacheAwareInterfaceToCacheEntryIdentifier()
    {
        $mockObject = $this->createMock(CacheAwareInterface::class);

        $mockObject->expects($this->atLeastOnce())->method('getCacheEntryIdentifier')->willReturn(('objectIdentifier'));

        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $cacheIdentifier = '868abeec5c300408f418bf198542daec';

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }
}
