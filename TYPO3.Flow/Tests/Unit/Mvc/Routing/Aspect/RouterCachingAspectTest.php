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
	 * @var \TYPO3\Flow\Mvc\Routing\RouterCachingService
	 */
	protected $mockRouterCachingService;

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

		$this->mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$this->routerCachingAspect->_set('routerCachingService', $this->mockRouterCachingService);

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
	 * @test
	 */
	public function cacheMatchingCallReturnsCachedMatchResultsIfFoundInCache() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$expectedResult = array('cached' => 'route values');
		$this->mockRouterCachingService->expects($this->once())->method('getCacheMatching')->with($this->mockHttpRequest)->will($this->returnValue($expectedResult));

		$actualResult = $this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheMatchingCallReturnsOriginalMatchResultsIfNotFoundInCache() {
		$this->mockJoinPoint->expects($this->any())->method('getMethodArgument')->with('httpRequest')->will($this->returnValue($this->mockHttpRequest));
		$expectedResult = array('uncached' => 'route values');
		$this->mockRouterCachingService->expects($this->once())->method('getCacheMatching')->with($this->mockHttpRequest)->will($this->returnValue(FALSE));

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

		$this->mockRouterCachingService->expects($this->once())->method('getCacheMatching')->with($this->mockHttpRequest)->will($this->returnValue(FALSE));
		$this->mockRouterCachingService->expects($this->once())->method('createCacheMatching')->with($this->mockHttpRequest, $matchResults);

		$this->routerCachingAspect->cacheMatchingCall($this->mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallReturnsCachedMatchingUriIfFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$expectedResult = 'cached/matching/uri';
		$this->mockRouterCachingService->expects($this->once())->method('getCacheResolve')->with($routeValues)->will($this->returnValue($expectedResult));
		$this->mockRouterCachingService->expects($this->never())->method('createCacheResolve');

		$actualResult = $this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function cacheResolveCallReturnsOriginalMatchingUriIfNotFoundInCache() {
		$routeValues = array('b' => 'route values', 'a' => 'Some more values');
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockRouterCachingService->expects($this->once())->method('getCacheResolve')->with($routeValues)->will($this->returnValue(FALSE));

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
		$this->mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('routeValues')->will($this->returnValue($routeValues));

		$this->mockRouterCachingService->expects($this->once())->method('getCacheResolve')->with($routeValues)->will($this->returnValue(FALSE));

		$matchingUri = 'uncached/matching/uri';
		$this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint)->will($this->returnValue($matchingUri));

		$this->mockRouterCachingService->expects($this->once())->method('createCacheResolve')->with($matchingUri, $routeValues);

		$this->routerCachingAspect->cacheResolveCall($this->mockJoinPoint);
	}

}
?>
