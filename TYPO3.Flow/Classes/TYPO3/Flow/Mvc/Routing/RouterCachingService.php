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
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Utility\Arrays;

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
	 * @param Request $httpRequest
	 * @return array|boolean the cached route values or FALSE if no cache entry was found
	 */
	public function getCachedMatchResults(Request $httpRequest) {
		$cachedResult = $this->findMatchResultsCache->get($this->buildFindMatchResultsCacheIdentifier($httpRequest));
		if ($cachedResult !== FALSE) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $this->buildFindMatchResultsCacheIdentifier($httpRequest), $httpRequest->getRelativePath()), LOG_DEBUG);
		}

		return $cachedResult;
	}

	/**
	 * Stores the $matchResults in the cache
	 *
	 * @param Request $httpRequest
	 * @param array $matchResults
	 * @return void
	 */
	public function storeMatchResults(Request $httpRequest, array $matchResults) {
		if ($this->containsObject($matchResults)) {
			return;
		}
		$this->findMatchResultsCache->set($this->buildFindMatchResultsCacheIdentifier($httpRequest), $matchResults);
	}

	/**
	 * Checks the cache for the given route values and returns the cached resolvedUriPath if a cache entry is found
	 *
	 * @param array $routeValues
	 * @return string|boolean the cached request path or FALSE if no cache entry was found
	 */
	public function getCachedResolvedUriPath(array $routeValues) {
		try {
			$routeValues = $this->convertObjectsToHashes($routeValues);
			return $this->resolveCache->get($this->buildResolveCacheIdentifier($routeValues));
		} catch (\InvalidArgumentException $exception) {
			return FALSE;
		}
	}

	/**
	 * Stores the $uriPath in the cache together with the $routeValues
	 *
	 * @param string $uriPath
	 * @param array $routeValues
	 * @return void
	 */
	public function storeResolvedUriPath($uriPath, array $routeValues) {
		try {
			$routeValues = $this->convertObjectsToHashes($routeValues);
		} catch (\InvalidArgumentException $exception) {
			return;
		}
		$cacheIdentifier = $this->buildResolveCacheIdentifier($routeValues);
		if ($cacheIdentifier !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $uriPath);
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
	 * @return boolean TRUE if $subject contains an object, otherwise FALSE
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
	 * @return array the modified array or NULL if $routeValues contain an object and its identifier could not be determined
	 * @throws \InvalidArgumentException if $routeValues contain an object and its identifier could not be determine
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

	/**
	 * Generates the Matching cache identifier for the given Request
	 *
	 * @param Request $httpRequest
	 * @return string
	 */
	protected function buildFindMatchResultsCacheIdentifier(Request $httpRequest) {
		return md5($httpRequest->getRelativePath()) . '_' . $httpRequest->getMethod();
	}

	/**
	 * Generates the Resolve cache identifier for the given Request
	 *
	 * @param array $routeValues
	 * @return string
	 */
	protected function buildResolveCacheIdentifier(array $routeValues) {
		Arrays::sortKeysRecursively($routeValues);
		return md5(http_build_query($routeValues));
	}

}