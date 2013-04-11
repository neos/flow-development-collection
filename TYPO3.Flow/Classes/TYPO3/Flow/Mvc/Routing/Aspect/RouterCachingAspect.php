<?php
namespace TYPO3\Flow\Mvc\Routing\Aspect;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class RouterCachingAspect {

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 * @Flow\Inject
	 */
	protected $findMatchResultsCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 * @Flow\Inject
	 */
	protected $resolveCache;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->findMatchResults())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function cacheMatchingCall(JoinPointInterface $joinPoint) {
		/** @var $httpRequest \TYPO3\Flow\Http\Request */
		$httpRequest = $joinPoint->getMethodArgument('httpRequest');
		$routePath = substr($httpRequest->getUri()->getPath(), strlen($httpRequest->getBaseUri()->getPath()));

		$cacheIdentifier = md5($routePath) . '_' . $httpRequest->getMethod();
		$cachedResult = $this->findMatchResultsCache->get($cacheIdentifier);
		if ($cachedResult !== FALSE) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $cacheIdentifier, $routePath), LOG_DEBUG);
			return $cachedResult;
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		$matchedRoute = $joinPoint->getProxy()->getLastMatchedRoute();
		if ($matchedRoute !== NULL) {
			$this->systemLogger->log(sprintf('Router route(): Route "%s" matched the path "%s".', $matchedRoute->getName(), $routePath), LOG_DEBUG);
		} else {
			$this->systemLogger->log(sprintf('Router route(): No route matched the route path "%s".', $routePath), LOG_NOTICE);
		}
		if ($matchResults !== NULL && $this->containsObject($matchResults) === FALSE) {
			$this->findMatchResultsCache->set($cacheIdentifier, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\Router->resolve())")
	 * @param JoinPointInterface $joinPoint The current join point
	 * @return string Result of the target method
	 */
	public function cacheResolveCall(JoinPointInterface $joinPoint) {
		$cacheIdentifier = NULL;
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		try {
			$routeValues = $this->convertObjectsToHashes($routeValues);
			\TYPO3\Flow\Utility\Arrays::sortKeysRecursively($routeValues);
			$cacheIdentifier = md5(http_build_query($routeValues));
			$cachedResult = $this->resolveCache->get($cacheIdentifier);
			if ($cachedResult !== FALSE) {
				return $cachedResult;
			}
		} catch (\InvalidArgumentException $exception) {
		}

		$matchingUri = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUri !== NULL && $cacheIdentifier !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $matchingUri);
		}
		return $matchingUri;
	}

	/**
	 * Flushes 'findMatchResults' and 'resolve' caches.
	 *
	 * @return void
	 */
	public function flushCaches() {
		$this->findMatchResultsCache->flush();
		$this->resolveCache->flush();
	}

	/**
	 * Checks if the given subject contains an object
	 *
	 * @param mixed $subject
	 * @return boolean If it contains an object or not
	 */
	protected function containsObject($subject) {
		if (is_object($subject)) {
			return TRUE;
		}
		if (!is_array($subject)) {
			return FALSE;
		}
		foreach ($subject as $value) {
			if ($this->containsObject($value)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Recursively converts objects in an array to their identifiers
	 *
	 * @param array $routeValues the array to be processed
	 * @return array the modified array
	 * @throws \InvalidArgumentException if $routeValues contain an object and its identifier could not be determined
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				$identifier = $this->persistenceManager->getIdentifierByObject($value);
				if ($identifier === NULL) {
					throw new \InvalidArgumentException(sprintf('The identifier of an object of type "%s" could not be determined', get_class($value)), 1340102526);
				}
				$value = $identifier;
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
			}
		}
		return $routeValues;
	}
}
?>