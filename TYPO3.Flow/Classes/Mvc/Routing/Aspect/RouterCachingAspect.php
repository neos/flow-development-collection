<?php
namespace TYPO3\FLOW3\Mvc\Routing\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @FLOW3\Aspect
 * @FLOW3\Scope("singleton")
 */
class RouterCachingAspect {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $findMatchResultsCache;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\StringFrontend
	 */
	protected $resolveCache;

	/**
	 * Injects the $findMatchResultsCache frontend
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function injectFindMatchResultsCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->findMatchResultsCache = $cache;
	}

	/**
	 * Injects the $resolveCache drontend
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\StringFrontend $cache
	 * @return void
	 */
	public function injectResolveCache(\TYPO3\FLOW3\Cache\Frontend\StringFrontend $cache) {
		$this->resolveCache = $cache;
	}

	/**
	 * Around advice
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Mvc\Routing\Router->findMatchResults())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 */
	public function cacheMatchingCall(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$routePath = $joinPoint->getMethodArgument('routePath');

		$cacheIdentifier = md5($routePath);
		if ($this->findMatchResultsCache->has($cacheIdentifier)) {
			return $this->findMatchResultsCache->get($cacheIdentifier);
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchResults !== NULL && $this->containsObject($matchResults) === FALSE) {
			$this->findMatchResultsCache->set($cacheIdentifier, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @FLOW3\Around("method(TYPO3\FLOW3\Mvc\Routing\Router->resolve())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return string Result of the target method
	 */
	public function cacheResolveCall(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		$routeValues = $this->convertObjectsToHashes($routeValues);
		\TYPO3\FLOW3\Utility\Arrays::sortKeysRecursively($routeValues);

		$cacheIdentifier = md5(http_build_query($routeValues));
		if ($this->resolveCache->has($cacheIdentifier)) {
			return $this->resolveCache->get($cacheIdentifier);
		}

		$matchingUri = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUri !== NULL) {
			$this->resolveCache->set($cacheIdentifier, $matchingUri);
		}
		return $matchingUri;
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
	 * Recursively converts objects in an array to their spl object hashes
	 *
	 * @param array $routeValues the array to be processed
	 * @return array the modified array
	 */
	protected function convertObjectsToHashes(array $routeValues) {
		foreach ($routeValues as &$value) {
			if (is_object($value)) {
				$value = spl_object_hash($value);
			} elseif (is_array($value)) {
				$value = $this->convertObjectsToHashes($value);
			}
		}
		return $routeValues;
	}
}
?>