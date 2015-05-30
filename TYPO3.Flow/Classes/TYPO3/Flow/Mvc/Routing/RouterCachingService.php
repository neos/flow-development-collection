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
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Validation\Validator\UuidValidator;

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
	protected $routeCache;

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
		$cachedResult = $this->routeCache->get($this->buildRouteCacheIdentifier($httpRequest));
		if ($cachedResult !== FALSE) {
			$this->systemLogger->log(sprintf('Router route(): A cached Route with the cache identifier "%s" matched the path "%s".', $this->buildRouteCacheIdentifier($httpRequest), $httpRequest->getRelativePath()), LOG_DEBUG);
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
		$this->routeCache->set($this->buildRouteCacheIdentifier($httpRequest), $matchResults, $this->extractUuids($matchResults));
	}

	/**
	 * Checks the cache for the given route values and returns the cached resolvedUriPath if a cache entry is found
	 *
	 * @param array $routeValues
	 * @return string|boolean the cached request path or FALSE if no cache entry was found
	 */
	public function getCachedResolvedUriPath(array $routeValues) {
		$routeValues = $this->convertObjectsToHashes($routeValues);
		if ($routeValues === NULL) {
			return FALSE;
		}
		return $this->resolveCache->get($this->buildResolveCacheIdentifier($routeValues));
	}

	/**
	 * Stores the $uriPath in the cache together with the $routeValues
	 *
	 * @param string $uriPath
	 * @param array $routeValues
	 * @return void
	 */
	public function storeResolvedUriPath($uriPath, array $routeValues) {
		$routeValues = $this->convertObjectsToHashes($routeValues);
		if ($routeValues === NULL) {
			return;
		}

		$cacheIdentifier = $this->buildResolveCacheIdentifier($routeValues);
		if ($cacheIdentifier !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $uriPath, $this->extractUuids($routeValues));
		}
	}

	/**
	 * Flushes 'route' and 'resolve' caches.
	 *
	 * @return void
	 */
	public function flushCaches() {
		$this->routeCache->flush();
		$this->resolveCache->flush();
	}

	/**
	 * Flushes 'findMatchResults' and 'resolve' caches for the given $tag
	 *
	 * @param string $tag
	 * @return void
	 */
	public function flushCachesByTag($tag) {
		$this->routeCache->flushByTag($tag);
		$this->resolveCache->flushByTag($tag);
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
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				if ($value instanceof CacheAwareInterface) {
					$identifier = $value->getCacheEntryIdentifier();
				} else {
					$identifier = $this->persistenceManager->getIdentifierByObject($value);
				}
				if ($identifier === NULL) {
					return NULL;
				}
				$value = $identifier;
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
				if ($value === NULL) {
					return NULL;
				}
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
	protected function buildRouteCacheIdentifier(Request $httpRequest) {
		return md5(sprintf('%s_%s_%s', $httpRequest->getUri()->getHost(), $httpRequest->getRelativePath(), $httpRequest->getMethod()));
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

	/**
	 * Helper method to generate tags by taking all UUIDs contained
	 * in the given $routeValues or $matchResults
	 *
	 * @param array $values
	 * @return array
	 */
	protected function extractUuids(array $values) {
		$uuids = array();
		foreach ($values as $value) {
			if (is_string($value)) {
				if (preg_match(UuidValidator::PATTERN_MATCH_UUID, $value) !== 0) {
					$uuids[] = $value;
				}
			} elseif (is_array($value)) {
				$uuids = array_merge($uuids, $this->extractUuids($value));
			}
		}
		return $uuids;
	}

}