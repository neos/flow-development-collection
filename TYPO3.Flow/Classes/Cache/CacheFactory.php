<?php
namespace TYPO3\FLOW3\Cache;

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
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class CacheFactory {

	/**
	 * The current FLOW3 context ("Production", "Development" etc.)
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * A reference to the cache manager
	 *
	 * @var \TYPO3\FLOW3\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * Constructs this cache factory
	 *
	 * @param string $context The current FLOW3 context
	 * @param \TYPO3\FLOW3\Cache\CacheManager $cacheManager
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 */
	public function __construct($context, \TYPO3\FLOW3\Cache\CacheManager $cacheManager, \TYPO3\FLOW3\Utility\Environment $environment) {
		$this->context = $context;
		$this->cacheManager = $cacheManager;
		$this->cacheManager->injectCacheFactory($this);
		$this->environment = $environment;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheObjectName Object name of the cache frontend
	 * @param string $backendObjectName Object name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return \TYPO3\FLOW3\Cache\Frontend\FrontendInterface The created cache frontend
	 * @throws \TYPO3\FLOW3\Cache\Exception\InvalidBackendException
	 * @throws \TYPO3\FLOW3\Cache\Exception\InvalidCacheException
	 * @api
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {
		$backend = new $backendObjectName($this->context, $backendOptions);
		if (!$backend instanceof \TYPO3\FLOW3\Cache\Backend\BackendInterface) {
			throw new \TYPO3\FLOW3\Cache\Exception\InvalidBackendException('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
		}
		$backend->injectEnvironment($this->environment);
		if (is_callable(array($backend, 'initializeObject'))) {
			$backend->initializeObject(\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
		}

		$cache = new $cacheObjectName($cacheIdentifier, $backend);
		if (!$cache instanceof \TYPO3\FLOW3\Cache\Frontend\FrontendInterface) {
			throw new \TYPO3\FLOW3\Cache\Exception\InvalidCacheException('"' . $cacheObjectName . '" is not a valid cache frontend object.', 1216304300);
		}
		if (is_callable(array($cache, 'initializeObject'))) {
			$cache->initializeObject(\TYPO3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
		}

		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}
?>