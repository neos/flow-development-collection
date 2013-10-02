<?php
namespace TYPO3\Flow\Mvc\Routing;

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

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @Flow\Scope("singleton")
 */
class RouterCachingService {

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
	 * Checks the cache for the route path given in the Request and returns the result
	 *
	 * @param $httpRequest \TYPO3\Flow\Http\Request
	 * @return mixed
	 */
	public function getCacheMatching($httpRequest) {
		$cachedResult = $this->findMatchResultsCache->get($this->getCacheMatchingIdentifier($httpRequest));
		if ($cachedResult !== FALSE) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $this->getCacheMatchingIdentifier($httpRequest), $this->getRoutePath($httpRequest)), LOG_DEBUG);
		}

		return $cachedResult;
	}

	/**
	 * Stores the $matchResults in the cache
	 *
	 * @param $httpRequest \TYPO3\Flow\Http\Request
	 * @param mixed $matchResults
	 * @return void
	 */
	public function createCacheMatching(\TYPO3\Flow\Http\Request $httpRequest, $matchResults) {
		if ($matchResults !== NULL && $this->containsObject($matchResults) === FALSE) {
			$this->findMatchResultsCache->set($this->getCacheMatchingIdentifier($httpRequest), $matchResults);
		}
	}

	/**
	 * Checks the cache for the given route values and returns the result
	 *
	 * @param array $routeValues
	 * @return string
	 */
	public function getCacheResolve(array $routeValues) {
		$routeValues = $this->convertObjectsToHashes($routeValues);
		if ($routeValues !== NULL) {
			$cachedResult = $this->resolveCache->get($this->getCacheResolveIdentifier($routeValues));
			return $cachedResult;
		}
	}

	/**
	 * Stores the $matchingUri in the cache
	 *
	 * @param string $matchingUri
	 * @param array $routeValues
	 * @return void
	 */
	public function createCacheResolve($matchingUri, array $routeValues) {
		$routeValues = $this->convertObjectsToHashes($routeValues);
		if ($routeValues !== NULL) {
			$cacheIdentifier = $this->getCacheResolveIdentifier($routeValues);
			if ($matchingUri !== NULL && $cacheIdentifier !== NULL) {
				$this->resolveCache->set($cacheIdentifier, $matchingUri);
			}
		}
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
	 * @return array|NULL the modified array or NULL if $routeValues contain an object and its identifier could not be determined
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				$identifier = $this->persistenceManager->getIdentifierByObject($value);
				if ($identifier === NULL) {
					return NULL;
				}
				$value = $identifier;
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
			}
		}
		return $routeValues;
	}

	/**
	 * Generates the Matching cache identifier for the given Request
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return string
	 */
	protected function getCacheMatchingIdentifier(\TYPO3\Flow\Http\Request $httpRequest) {
		return md5($this->getRoutePath($httpRequest)) . '_' . $httpRequest->getMethod();
	}

	/**
	 * Generates the Resolve cache identifier for the given Request
	 *
	 * @param array $routeValues
	 * @return string
	 */
	protected function getCacheResolveIdentifier(array $routeValues) {
		\TYPO3\Flow\Utility\Arrays::sortKeysRecursively($routeValues);
		return md5(http_build_query($routeValues));
	}

	/**
	 * Returns the route path for the given Request
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return string
	 */
	public function getRoutePath(\TYPO3\Flow\Http\Request $httpRequest) {
		return substr($httpRequest->getUri()->getPath(), strlen($httpRequest->getBaseUri()->getPath()));
	}

}