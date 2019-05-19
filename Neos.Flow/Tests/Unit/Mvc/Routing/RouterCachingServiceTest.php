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
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
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
     * @var VariableFrontend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRouteCache;

    /**
     * @var StringFrontend|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResolveCache;

    /**
     * @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var ApplicationContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApplicationContext;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var Uri|\PHPUnit_Framework_MockObject_MockObject
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
        $this->routerCachingService->injectLogger($this->mockSystemLogger);

        $this->mockObjectManager  = $this->createMock(ObjectManagerInterface::class);
        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager->expects($this->any())->method('getContext')->will($this->returnValue($this->mockApplicationContext));
        $this->inject($this->routerCachingService, 'objectManager', $this->mockObjectManager);

        $this->inject($this->routerCachingService, 'objectManager', $this->mockObjectManager);

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
        $this->mockHttpRequest->expects($this->any())->method('getRelativePath')->will($this->returnValue('some/route/path'));
        $this->mockUri = $this->getMockBuilder(Uri::class)->disableOriginalConstructor()->getMock();
        $this->mockUri->expects($this->any())->method('getHost')->will($this->returnValue('subdomain.domain.com'));
        $this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockUri));
    }

    /**
     * @test
     */
    public function initializeObjectDoesNotFlushCachesInProductionContext()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(false));
        $this->mockRouteCache->expects($this->never())->method('get');
        $this->mockRouteCache->expects($this->never())->method('flush');
        $this->mockResolveCache->expects($this->never())->method('flush');

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

        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->will($this->returnValue($cachedRoutingSettings));

        $this->mockRouteCache->expects($this->never())->method('flush');
        $this->mockResolveCache->expects($this->never())->method('flush');

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

        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->will($this->returnValue($cachedRoutingSettings));

        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');

        $this->routerCachingService->_call('initializeObject');
    }

    /**
     * @test
     */
    public function initializeFlushesCachesInDevelopmentContextIfRoutingSettingsWhereNotStoredPreviously()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));
        $this->mockRouteCache->expects($this->atLeastOnce())->method('get')->with('routingSettings')->will($this->returnValue(false));

        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');

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
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCachedMatchResultsReturnsCachedMatchResultsIfFoundInCache()
    {
        $expectedResult = ['cached' => 'route values'];
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function getCachedMatchResultsReturnsFalseIfNotFoundInCache()
    {
        $expectedResult = false;
        $cacheIdentifier = '095d44631b8d13717d5fb3d2f6c3e032';
        $this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(false));

        $actualResult = $this->routerCachingService->getCachedMatchResults(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storeMatchResultsDoesNotStoreMatchResultsInCacheIfTheyContainObjects()
    {
        $matchResults = ['this' => ['contains' => ['objects', new \stdClass()]]];

        $this->mockRouteCache->expects($this->never())->method('set');

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

        $this->mockRouteCache->expects($this->once())->method('set')->with($routeContext->getCacheEntryIdentifier(), $matchResults, [$uuid1, $uuid2, md5('some'), md5('some/route'), md5('some/route/path')]);

        $this->routerCachingService->storeMatchResults($routeContext, $matchResults);
    }

    /**
     * @test
     */
    public function getCachedResolvedUriReturnsCachedResolvedUriConstraintsIfFoundInCache()
    {
        $routeValues = ['b' => 'route values', 'a' => 'Some more values'];

        $expectedResult = UriConstraints::create()->withPath('cached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('get')->will($this->returnValue($expectedResult));

        $actualResult = $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false));
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsConvertsObjectsToHashesToGenerateCacheIdentifier()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];
        $cacheIdentifier = '415bf8745c304076a7139e4f4fcf2eb1';

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('objectIdentifier'));

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false), $resolvedUriConstraints);
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

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue($mockUuid));

        $resolvedUriConstraints = UriConstraints::create()->withPath('path');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints, [$mockUuid, md5('path')]);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false), $resolvedUriConstraints);
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsExtractsUuidsToCacheTags()
    {
        $uuid1 = '550e8400-e29b-11d4-a716-446655440000';
        $uuid2 = '302abe9c-7d07-4200-a868-478586019290';
        $routeValues = ['some' => ['routeValues' => ['uuid', $uuid1]], 'foo' => $uuid2];
        $resolveContext = new ResolveContext($this->mockUri, $routeValues, false);
        $resolvedUriConstraints = UriConstraints::create()->withPath('some/request/path');

        /** @var RouterCachingService|\PHPUnit_Framework_MockObject_MockObject $routerCachingService */
        $routerCachingService = $this->getAccessibleMock(RouterCachingService::class, ['buildResolveCacheIdentifier']);
        $routerCachingService->expects($this->atLeastOnce())->method('buildResolveCacheIdentifier')->with($resolveContext, $routeValues)->will($this->returnValue('cacheIdentifier'));
        $this->inject($routerCachingService, 'resolveCache', $this->mockResolveCache);

        $this->mockResolveCache->expects($this->once())->method('set')->with('cacheIdentifier', $resolvedUriConstraints, [$uuid1, $uuid2, md5('some'), md5('some/request'), md5('some/request/path')]);

        $routerCachingService->storeResolvedUriConstraints($resolveContext, $resolvedUriConstraints);
    }

    /**
     * @test
     */
    public function getCachedResolvedUriConstraintSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes()
    {
        $mockObject = new \stdClass();
        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(null));

        $this->mockResolveCache->expects($this->never())->method('has');
        $this->mockResolveCache->expects($this->never())->method('set');

        $this->routerCachingService->getCachedResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false));
    }

    /**
     * @test
     */
    public function flushCachesResetsBothRoutingCaches()
    {
        $this->mockRouteCache->expects($this->once())->method('flush');
        $this->mockResolveCache->expects($this->once())->method('flush');
        $this->routerCachingService->flushCaches();
    }

    /**
     * @test
     */
    public function storeResolvedUriConstraintsConvertsObjectsImplementingCacheAwareInterfaceToCacheEntryIdentifier()
    {
        $mockObject = $this->createMock(CacheAwareInterface::class);

        $mockObject->expects($this->atLeastOnce())->method('getCacheEntryIdentifier')->will($this->returnValue('objectIdentifier'));

        $routeValues = ['b' => 'route values', 'someObject' => $mockObject];

        $cacheIdentifier = '415bf8745c304076a7139e4f4fcf2eb1';

        $resolvedUriConstraints = UriConstraints::create()->withPath('uncached/matching/uri');
        $this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $resolvedUriConstraints);

        $this->routerCachingService->storeResolvedUriConstraints(new ResolveContext($this->mockUri, $routeValues, false), $resolvedUriConstraints);
    }
}
