<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Cache\Frontend\StringFrontend;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Router Caching Service
 *
 */
class RouterCachingServiceTest extends UnitTestCase {

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
	 * @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSystemLogger;

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
	public function setUp() {
		$this->routerCachingService = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\RouterCachingService', array('dummy'));

		$this->mockRouteCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\VariableFrontend')->disableOriginalConstructor()->getMock();
		$this->inject($this->routerCachingService, 'routeCache', $this->mockRouteCache);

		$this->mockResolveCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->getMock();
		$this->inject($this->routerCachingService, 'resolveCache', $this->mockResolveCache);

		$this->mockPersistenceManager  = $this->getMockBuilder('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getMock();
		$this->inject($this->routerCachingService, 'persistenceManager', $this->mockPersistenceManager);

		$this->mockSystemLogger  = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$this->inject($this->routerCachingService, 'systemLogger', $this->mockSystemLogger);

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockHttpRequest->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
		$this->mockHttpRequest->expects($this->any())->method('getRelativePath')->will($this->returnValue('some/route/path'));
		$this->mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$this->mockUri->expects($this->any())->method('getHost')->will($this->returnValue('subdomain.domain.com'));
		$this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockUri));
	}

	/**
	 * Data provider for containsObjectDetectsObjectsInVariousSituations()
	 */
	public function containsObjectDetectsObjectsInVariousSituationsDataProvider() {
		$object = new \stdClass();
		return array(
			array(TRUE, $object),
			array(TRUE, array('foo' => $object)),
			array(TRUE, array('foo' => 'bar', 'baz' => $object)),
			array(TRUE, array('foo' => array('bar' => array('baz' => 'quux', 'here' => $object)))),
			array(FALSE, 'no object'),
			array(FALSE, array('foo' => 'no object')),
			array(FALSE, TRUE)
		);
	}

	/**
	 * @dataProvider containsObjectDetectsObjectsInVariousSituationsDataProvider()
	 * @test
	 */
	public function containsObjectDetectsObjectsInVariousSituations($expectedResult, $subject) {
		$actualResult = $this->routerCachingService->_call('containsObject', $subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCachedMatchResultsReturnsCachedMatchResultsIfFoundInCache() {
		$expectedResult = array('cached' => 'route values');
		$cacheIdentifier = '89dcfa70030cbdf762b727b5ba41c7bb';
		$this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingService->getCachedMatchResults($this->mockHttpRequest);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCachedMatchResultsReturnsFalseIfNotFoundInCache() {
		$expectedResult = FALSE;
		$cacheIdentifier = '89dcfa70030cbdf762b727b5ba41c7bb';
		$this->mockRouteCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$actualResult = $this->routerCachingService->getCachedMatchResults($this->mockHttpRequest);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function storeMatchResultsDoesNotStoreMatchResultsInCacheIfTheyContainObjects() {
		$matchResults = array('this' => array('contains' => array('objects', new \stdClass())));

		$this->mockRouteCache->expects($this->never())->method('set');

		$this->routerCachingService->storeMatchResults($this->mockHttpRequest, $matchResults);
	}

	/**
	 * @test
	 */
	public function storeMatchExtractsUuidsToCacheTags() {
		$uuid1 = '550e8400-e29b-11d4-a716-446655440000';
		$uuid2 = '302abe9c-7d07-4200-a868-478586019290';
		$matchResults = array('some' => array('matchResults' => array('uuid', $uuid1)), 'foo' => $uuid2);

		/** @var RouterCachingService|\PHPUnit_Framework_MockObject_MockObject $routerCachingService */
		$routerCachingService = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\RouterCachingService', array('buildRouteCacheIdentifier'));
		$routerCachingService->expects($this->atLeastOnce())->method('buildRouteCacheIdentifier')->with($this->mockHttpRequest)->will($this->returnValue('cacheIdentifier'));
		$this->inject($routerCachingService, 'routeCache', $this->mockRouteCache);

		$this->mockRouteCache->expects($this->once())->method('set')->with('cacheIdentifier', $matchResults, array($uuid1, $uuid2));

		$routerCachingService->storeMatchResults($this->mockHttpRequest, $matchResults);
	}

	/**
	 * @test
	 */
	public function getCachedResolvedUriPathReturnsCachedResolvedUriPathIfFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';

		$expectedResult = 'cached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingService->getCachedResolvedUriPath($routeValues);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function storeResolvedUriPathConvertsObjectsToHashesToGenerateCacheIdentifier() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);
		$cacheIdentifier = '264b593d59582adea4ccc52b33cc093f';

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('objectIdentifier'));

		$matchingUriPath = 'uncached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchingUriPath);

		$this->routerCachingService->storeResolvedUriPath($matchingUriPath, $routeValues);
	}

	/**
	 * @test
	 */
	public function storeResolvedUriPathExtractsUuidsToCacheTags() {
		$resolvedUriPath = 'some/request/path';
		$uuid1 = '550e8400-e29b-11d4-a716-446655440000';
		$uuid2 = '302abe9c-7d07-4200-a868-478586019290';
		$routeValues = array('some' => array('routeValues' => array('uuid', $uuid1)), 'foo' => $uuid2);

		$routerCachingService = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\RouterCachingService', array('buildResolveCacheIdentifier'));
		$routerCachingService->expects($this->atLeastOnce())->method('buildResolveCacheIdentifier')->with($routeValues)->will($this->returnValue('cacheIdentifier'));
		$this->inject($routerCachingService, 'resolveCache', $this->mockResolveCache);

		$this->mockResolveCache->expects($this->once())->method('set')->with('cacheIdentifier', $resolvedUriPath, array($uuid1, $uuid2));

		$routerCachingService->storeResolvedUriPath($resolvedUriPath, $routeValues);
	}

	/**
	 * @test
	 */
	public function getCachedResolvedUriPathSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(NULL));

		$this->mockResolveCache->expects($this->never())->method('has');
		$this->mockResolveCache->expects($this->never())->method('set');

		$this->routerCachingService->getCachedResolvedUriPath($routeValues);
	}

	/**
	 * @test
	 */
	public function flushCachesResetsBothRoutingCaches() {
		$this->mockRouteCache->expects($this->once())->method('flush');
		$this->mockResolveCache->expects($this->once())->method('flush');
		$this->routerCachingService->flushCaches();
	}

	/**
	 * @test
	 */
	public function storeResolvedUriPathConvertsObjectsImplementingCacheAwareInterfaceToCacheEntryIdentifier() {
		$mockObject = $this->getMock('TYPO3\Flow\Cache\CacheAwareInterface');

		$mockObject->expects($this->atLeastOnce())->method('getCacheEntryIdentifier')->will($this->returnValue('objectIdentifier'));

		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);

		$cacheIdentifier = '264b593d59582adea4ccc52b33cc093f';

		$matchingUriPath = 'uncached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchingUriPath);

		$this->routerCachingService->storeResolvedUriPath($matchingUriPath, $routeValues);
	}

}
