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

/**
 * Testcase for the Router Caching Service
 *
 */
class RouterCachingServiceTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\RouterCachingService
	 */
	protected $routerCachingService;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $mockFindMatchResultsCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $mockResolveCache;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $mockSystemLogger;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $mockHttpRequest;

	/**
	 * @var \TYPO3\Flow\Http\Uri
	 */
	protected $mockUri;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->routerCachingService = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\RouterCachingService', array('dummy'));

		$this->mockFindMatchResultsCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\VariableFrontend')->disableOriginalConstructor()->getMock();
		$this->routerCachingService->_set('findMatchResultsCache', $this->mockFindMatchResultsCache);

		$this->mockResolveCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->getMock();
		$this->routerCachingService->_set('resolveCache', $this->mockResolveCache);

		$this->mockPersistenceManager  = $this->getMockBuilder('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getMock();
		$this->routerCachingService->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockSystemLogger  = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$this->routerCachingService->_set('systemLogger', $this->mockSystemLogger);

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockHttpRequest->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));

		$this->mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$this->mockUri->expects($this->any())->method('getPath')->will($this->returnValue('/some/route/path'));
		$this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockUri));

		$mockBaseUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockBaseUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
		$this->mockHttpRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue($mockBaseUri));
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
	public function getCacheMatchingReturnsCachedMatchResultsIfFoundInCache() {
		$expectedResult = array('cached' => 'route values');
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingService->getCacheMatching($this->mockHttpRequest);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function getCacheMatchingReturnsFalseIfNotFoundInCache() {
		$expectedResult = FALSE;
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$actualResult = $this->routerCachingService->getCacheMatching($this->mockHttpRequest);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function createCacheMatchingDoesNotStoreMatchResultsInCacheIfItsNull() {
		$matchResults = NULL;

		$this->mockFindMatchResultsCache->expects($this->never())->method('set');

		$this->routerCachingService->createCacheMatching($this->mockHttpRequest, $matchResults);
	}

	/**
	 * @test
	 */
	public function createCacheMatchingDoesNotStoreMatchResultsInCacheIfTheyContainObjects() {
		$matchResults = array('this' => array('contains' => array('objects', new \stdClass())));

		$this->mockFindMatchResultsCache->expects($this->never())->method('set');

		$this->routerCachingService->createCacheMatching($this->mockHttpRequest, $matchResults);
	}

	/**
	 * @test
	 */
	public function getCacheResolveReturnsCachedMatchingUriIfFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';

		$expectedResult = 'cached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingService->getCacheResolve($routeValues);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function createCacheResolveDoesNotStoreMatchingUriInCacheIfItsNull() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');

		$this->mockResolveCache->expects($this->never())->method('set');

		$this->routerCachingService->createCacheResolve(NULL, $routeValues);
	}

	/**
	 * @test
	 */
	public function createCacheResolveConvertsObjectsToHashesToGenerateCacheIdentifier() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);
		$cacheIdentifier = '264b593d59582adea4ccc52b33cc093f';

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('objectIdentifier'));

		$matchingUri = 'uncached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchingUri);

		$this->routerCachingService->createCacheResolve($matchingUri, $routeValues);
	}

	/**
	 * @test
	 */
	public function getCacheResolveSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(NULL));

		$this->mockResolveCache->expects($this->never())->method('has');
		$this->mockResolveCache->expects($this->never())->method('set');

		$this->routerCachingService->getCacheResolve($routeValues);
	}

	/**
	 * @test
	 */
	public function flushCachesResetsBothRoutingCaches() {
		$this->mockFindMatchResultsCache->expects($this->once())->method('flush');
		$this->mockResolveCache->expects($this->once())->method('flush');
		$this->routerCachingService->flushCaches();
	}

}
?>
