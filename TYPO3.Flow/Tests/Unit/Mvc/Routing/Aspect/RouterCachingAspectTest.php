<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing\Aspect;

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
 * Testcase for the Router Caching Aspect
 *
 */
class RouterCachingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect
	 */
	protected $routerCachingAspect;

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
	 * @var \TYPO3\Flow\Aop\JoinPointInterface
	 */
	protected $mockJoinPoint;

	/**
	 * @var \TYPO3\Flow\Aop\Advice\AdviceChain
	 */
	protected $mockAdviceChain;

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
		$this->routerCachingAspect = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Aspect\RouterCachingAspect', array('dummy'));
		$this->mockFindMatchResultsCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\VariableFrontend')->disableOriginalConstructor()->getMock();
		$this->routerCachingAspect->_set('findMatchResultsCache', $this->mockFindMatchResultsCache);

		$this->mockResolveCache = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\StringFrontend')->disableOriginalConstructor()->getMock();
		$this->routerCachingAspect->_set('resolveCache', $this->mockResolveCache);

		$this->mockPersistenceManager  = $this->getMockBuilder('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getMock();
		$this->routerCachingAspect->_set('persistenceManager', $this->mockPersistenceManager);

		$this->mockSystemLogger  = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$this->routerCachingAspect->_set('systemLogger', $this->mockSystemLogger);

		$this->mockAdviceChain = $this->getMockBuilder('TYPO3\Flow\Aop\Advice\AdviceChain')->disableOriginalConstructor()->getMock();
		$this->mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->getMock();
		$this->mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));

		$mockRouter = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->getMock();
		$this->mockJoinPoint->expects($this->any())->method('getProxy')->will($this->returnValue($mockRouter));

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
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$actualResult = $this->routerCachingAspect->_call('containsObject', $subject);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallReturnsCachedMatchResultsIfFoundInCache() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$expectedResult = array('cached' => 'route values');
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallReturnsOriginalMatchResultsIfNotFoundInCache() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$expectedResult = array('uncached' => 'route values');
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallStoresMatchResultsInCacheIfNotFoundInCache() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$matchResults = array('uncached' => 'route values');
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchResults));

		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));
		$this->mockFindMatchResultsCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchResults);

		$this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallDoesNotStoreMatchResultsInCacheIfItsNull() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$matchResults = NULL;
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchResults));

		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));
		$this->mockFindMatchResultsCache->expects($this->never())->method('set');

		$this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallDoesNotStoreMatchResultsInCacheIfTheyContainObjects() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$matchResults = array('this' => array('contains' => array('objects', new \stdClass())));
		$cacheIdentifier = 'e6e764c779e0b77420701a0943dd898f_GET';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchResults));

		$this->mockFindMatchResultsCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));
		$this->mockFindMatchResultsCache->expects($this->never())->method('set');

		$this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallReturnsCachedMatchingUriIfFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$expectedResult = 'cached/matching/uri';
		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallReturnsOriginalMatchingUriIfNotFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$expectedResult = 'uncached/matching/uri';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallStoresMatchingUriInCacheIfNotFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$matchingUri = 'uncached/matching/uri';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchingUri));
		$this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchingUri);

		$this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallDoesNotStoreMatchingUriInCacheIfItsNull() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$cacheIdentifier = '88a1c4366ca37b55e53905d61e184d08';
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallConvertsObjectsToHashesToGenerateCacheIdentifier() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);
		$cacheIdentifier = '264b593d59582adea4ccc52b33cc093f';
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockResolveCache->expects($this->once())->method('get')->with($cacheIdentifier)->will($this->returnValue(FALSE));

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue('objectIdentifier'));

		$matchingUri = 'uncached/matching/uri';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchingUri));
		$this->mockResolveCache->expects($this->once())->method('set')->with($cacheIdentifier, $matchingUri);

		$this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallSkipsCacheIfRouteValuesContainObjectsThatCantBeConvertedToHashes() {
		$mockObject = new \stdClass();
		$routeValues = array('b' => 'route values', 'someObject' => $mockObject);
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($mockObject)->will($this->returnValue(NULL));

		$matchingUri = 'uncached/matching/uri';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchingUri));
		$this->mockResolveCache->expects($this->never())->method('has');
		$this->mockResolveCache->expects($this->never())->method('set');

		$this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function flushCachesResetsBothRoutingCaches() {
		$this->mockFindMatchResultsCache->expects($this->once())->method('flush');
		$this->mockResolveCache->expects($this->once())->method('flush');
		$this->routerCachingAspect->flushCaches();
	}

}
?>
