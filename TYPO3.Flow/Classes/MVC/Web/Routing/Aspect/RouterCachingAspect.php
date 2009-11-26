<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Caching of findMatchResults() and resolve() calls on the web Router.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @aspect
 */
class RouterCachingAspect {

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $findMatchResultsCache;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\StringFrontend
	 */
	protected $resolveCache;

	/**
	 * Injects the $findMatchResultsCache frontend
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function injectFindMatchResultsCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->findMatchResultsCache = $cache;
	}

	/**
	 * Injects the $resolveCache drontend
	 *
	 * @param \F3\FLOW3\Cache\Frontend\StringFrontend $cache
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectResolveCache(\F3\FLOW3\Cache\Frontend\StringFrontend $cache) {
		$this->resolveCache = $cache;
	}

	/**
	 * Around advice
	 *
	 * @around method(F3\FLOW3\MVC\Web\Routing\Router->findMatchResults())
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return array Result of the target method
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function cacheMatchingCall(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$requestPath = $joinPoint->getMethodArgument('requestPath');

		$cacheIdentifier = md5($requestPath);
		if ($this->findMatchResultsCache->has($cacheIdentifier)) {
			return $this->findMatchResultsCache->get($cacheIdentifier);
		}

		$matchResults = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchResults !== NULL) {
			$this->findMatchResultsCache->set($cacheIdentifier, $matchResults);
		}
		return $matchResults;
	}

	/**
	 * Around advice
	 *
	 * @around method(F3\FLOW3\MVC\Web\Routing\Router->resolve())
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return string Result of the target method
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function cacheResolveCall(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$routeValues = $joinPoint->getMethodArgument('routeValues');
		$routeValues = $this->convertObjectsToHashes($routeValues);
		\F3\FLOW3\Utility\Arrays::sortKeysRecursively($routeValues);

		$cacheIdentifier = md5(http_build_query($routeValues));
		if ($this->resolveCache->has($cacheIdentifier)) {
			return $this->resolveCache->get($cacheIdentifier);
		}

		$matchingUri = $joinPoint->getAdviceChain()->proceed($joinPoint);
		if ($matchingUri !== '') {
			$this->resolveCache->set($cacheIdentifier, $matchingUri);
		}
		return $matchingUri;
	}

	/**
	 * Recursively converts objects in an array to their spl object hashes
	 *
	 * @param array $routeValues the array to be processed
	 * @return array the modified array
	 * @author Bastian Waidelich <bastian@typo3.org>
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