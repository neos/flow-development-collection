<?php
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

use Neos\Cache\CacheAwareInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\ApplicationContext;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the Router Caching Service
 *
 */
class RouterCachingServiceTest extends UnitTestCase
{
    /**
     * @var RouterCachingService
     */
    protected $routerCachingService;

    /**
     * @var VariableFrontend|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRouteCache;

    /**
     * @var StringFrontend|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockResolveCache;

    /**
     * @var PersistenceManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var ApplicationContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockApplicationContext;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockUri;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->routerCachingService = $this->getAccessibleMock(RouterCachingService::class, ['dummy']);

        $this->mockRouteCache = $this->getMockBuilder(VariableFrontend::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->routerCachingService, 'routeCache', $this->mockRouteCache);

        $this->mockResolveCache = $this->getMockBuilder(StringFrontend::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->routerCachingService, 'resolveCache', $this->mockResolveCache);

        $this->mockPersistenceManager  = $this->getMockBuilder(PersistenceManagerInterface::class)->getMock();
        $this->inject($this->routerCachingService, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockSystemLogger  = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->inject($this->routerCachingService, 'logger', $this->mockSystemLogger);

        $this->mockObjectManager  = $this->createMock(ObjectManagerInterface::class);
        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects(self::any())->method('getContext')->will(self::returnValue($this->mockApplicationContext));
        $this->inject($this->routerCachingService, 'objectManager', $this->mockObjectManager);

        $this->inject($this->routerCachingService, 'objectManager', $this->mockObjectManager);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects(self::any())->method('getMethod')->will(self::returnValue('GET'));
        $this->mockUri = new Uri('http://subdomain.domain.com/some/route/path');
        $this->mockHttpRequest->expects(self::any())->method('getUri')->will(self::returnValue($this->mockUri));
    }

    /**
     * @test
     */
    public function initializeObjectDoesNotFlushCachesInProductionContext()
    {
        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(false));
        $this->mockRouteCache->expects(self::never())->method('get');
        $this->mockRouteCache->expects(self::never())->method('flush');
        $this->mockResolveCache->expects(self::never())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * @test
     */
    public function initializeDoesNotFlushCachesInDevelopmentContextIfRoutingSettingsHaveNotChanged()
    {
        $cachedRoutingSettings = ['Some.Package' => true, 'Some.OtherPackage' => ['position' => 'start', 'suffix' => 'Foo', 'variables' => ['foo' => 'bar']]];

        $actualRoutingSettings = $cachedRoutingSettings;

        $this->inject($this->routerCachingService, 'routingSettings', $actualRoutingSettings);

        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(true));
        $this->mockRouteCache->expects(self::atLeastOnce())->method('get')->with('routingSettings')->will(self::returnValue($cachedRoutingSettings));

        $this->mockRouteCache->expects(self::never())->method('flush');
        $this->mockResolveCache->expects(self::never())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * @test
     */
    public function initializeFlushesCachesInDevelopmentContextIfRoutingSettingsHaveChanged()
    {
        $cachedRoutingSettings = ['Some.Package' => true, 'Some.OtherPackage' => ['position' => 'start', 'suffix' => 'Foo', 'variables' => ['foo' => 'bar']]];

        $actualRoutingSettings = $cachedRoutingSettings;
        $actualRoutingSettings['Some.OtherPackage']['variables']['foo'] = 'baz';

        $this->inject($this->routerCachingService, 'routingSettings', $actualRoutingSettings);

        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(true));
        $this->mockRouteCache->expects(self::atLeastOnce())->method('get')->with('routingSettings')->will(self::returnValue($cachedRoutingSettings));

        $this->mockRouteCache->expects(self::once())->method('flush');
        $this->mockResolveCache->expects(self::once())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * @test
     */
    public function initializeFlushesCachesInDevelopmentContextIfRoutingSettingsWhereNotStoredPreviously()
    {
        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(true));
        $this->mockRouteCache->expects(self::atLeastOnce())->method('get')->with('routingSettings')->will(self::returnValue(false));

        $this->mockRouteCache->expects(self::once())->method('flush');
        $this->mockResolveCache->expects(self::once())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * Data provider for containsObjectDetectsObjectsInVariousSituations()
     */
    public function containsObjectDetectsObjectsInVariousSituationsDataProvider()
    {
        $object = new \stdClass();
        return [
            [true, $object],
            [true, ['foo' => $object]],
            [true, ['foo' => 'bar', 'baz' => $object]],
            [true, ['foo' => ['bar' => ['baz' => 'quux', 'here' => $object]]]],
            [false, 'no object'],
            [false, ['foo' => 'no object']],
            [false, true]
        ];
    }

    /**
     * @dataProvider containsObjectDetectsObjectsInVariousSituationsDataProvider()
     * @test
     */
    public function containsObjectDetectsObjectsInVariousSituations($expectedResult, $subject)
    {
        $actualResult = $this->routerCachingService->_call('containsObject', $subject);
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCachedMatchResultsReturnsCachedMatchResultsIfFoundInCache()
    {
        $expectedResult = ['cached' => 'route values'];
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects(self::once())->method('get')->with($cacheIdentifier)->will(self::returnValue($expectedResult));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCachedMatchResultsReturnsFalseIfNotFoundInCache()
    {
        $expectedResult = false;
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects(self::once())->method('get')->with($cacheIdentifier)->will(self::returnValue(false));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storeMatchResultsDoesNotStoreMatchResultsInCacheIfTheyContainObjects()
    {
        $matchResults = ['this' => ['contains' => ['objects', new \stdClass()]]];

        $this->mockRouteCache->expects(self::never())->method('set');

        $this->routerCachingService->storeMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()), $matchResults);
    }

    /**
     * @test
     */
    public function storeMatchExtractsUuidsAndTheHashedUriPathToCacheTags()
    {
        $uuid1 = '550e8400-e29b-11d4-a716-446655440000';
        $uuid2 = '302abe9c-7d07-4200-a868-478586019290';
        $matchResults = ['some' => ['matchResults' => ['uuid', $uuid1]], 'foo' => $uuid2];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $this->mockRouteCache->expects(self::once())->method('set')->with($routeContext->getCacheEntryIdentifier(), $matchResults, [$uuid1, $uuid2, md5('some'), md5('some/route'), md5('some/route/path')]);

        $this->routerCachingService->storeMatchResults($routeContext, $matchResults);
    }

    /**
     * @test
     */
    public function getCachedResolvedUriReturnsCachedResolvedUriConstraintsIfFoundInCache()
    {
        $routeValues = ['b' => 'route values', 'a' => 'Some more values'];

        $expectedResult = UriConstraints::create()->withPath('cached/matching/uri');
        $this->mockResolveCache->expects(self::once())->method('get')->will(self::returnValue($expectedResult));

        $actualResult = $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()));
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsConvertsObjectsToHashesToGenerateCacheIdentifier()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];
        $cacheIdentifier = '415bf8745c304076a7139e4f4fcf2eb1';

        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($mockObject)->will(self::returnValue('objectIdentifier'));

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects(self::once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsConvertsObjectsToHashesToGenerateRouteTags()
    {
        $mockUuid = '550e8400-e29b-11d4-a716-446655440000';
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];
        $cacheIdentifier = 'e56bffd69837730b19089d3cf1eb7af9';

        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($mockObject)->will(self::returnValue($mockUuid));

        $resolvedUriConstraints = UriConstraints::create()->withPath('path');
        $this->mockResolveCache->expects(self::once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints, [$mockUuid, md5('path')]);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsExtractsUuidsToCacheTags()
    {
        $uuid1 = '550e8400-e29b-11d4-a716-446655440000';
        $uuid2 = '302abe9c-7d07-4200-a868-478586019290';
        $routeValues = ['some' => ['routeValues' => ['uuid', $uuid1]], 'foo' => $uuid2];
        $resolveContext = new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty());
        $resolvedUriConstraints = UriConstraints::create()->withPath('some/request/path');

        /** @var RouterCachingService|\PHPUnit\Framework\MockObject\MockObject $routerCachingService */
        $routerCachingService = $this->getAccessibleMock(RouterCachingService::class, ['buildResolveCacheIdentifier']);
        $routerCachingService->expects(self::atLeastOnce())->method('buildResolveCacheIdentifier')->with($resolveContext, $routeValues)->will(self::returnValue('cacheIdentifier'));
        $this->inject($routerCachingService, 'resolveCache', $this->mockResolveCache);

        $this->mockResolveCache->expects(self::once())->method('set')->with('cacheIdentifier', $resolvedUriConstraints, [$uuid1, $uuid2, md5('some'), md5('some/request'), md5('some/request/path')]);

        $routerCachingService->storeResolvedUriConstraints($resolveContext, $resolvedUriConstraints);
    }

    /**
     * @test
     */
    public function getCachedResolvedUriConstraintSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($mockObject)->will(self::returnValue(null));

        $this->mockResolveCache->expects(self::never())->method('has');
        $this->mockResolveCache->expects(self::never())->method('set');

        $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()));
    }

    /**
     * @test
     */
    public function flushCachesResetsBothRoutingCaches()
    {
        $this->mockRouteCache->expects(self::once())->method('flush');
        $this->mockResolveCache->expects(self::once())->method('flush');
        $this->routerCachingService->flushCaches();
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsConvertsObjectsImplementingCacheAwareInterfaceToCacheEntryIdentifier()
    {
        $mockObject = $this->createMock(CacheAwareInterface::class);

        $mockObject->expects(self::atLeastOnce())->method('getCacheEntryIdentifier')->will(self::returnValue('objectIdentifier'));

        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $cacheIdentifier = '415bf8745c304076a7139e4f4fcf2eb1';

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects(self::once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false, '', RouteParameters::createEmpty()), $resolvedUriConstraints);
    }
}
