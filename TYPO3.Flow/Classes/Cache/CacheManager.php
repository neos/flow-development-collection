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

use TYPO3\FLOW3\Cache\Frontend\FrontendInterface;

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The Cache Manager
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class CacheManager {

	/**
	 * @var \TYPO3\FLOW3\Cache\CacheFactory
	 */
	protected $cacheFactory;

	/**
	 * @var array
	 */
	protected $caches = array();

	/**
	 * @var array
	 */
	protected $cacheConfigurations = array(
		'Default' => array(
			'frontend' => 'TYPO3\FLOW3\Cache\Frontend\VariableFrontend',
			'backend' =>  'TYPO3\FLOW3\Cache\Backend\FileBackend',
			'backendOptions' => array()
		)
	);

	/**
	 * @param \TYPO3\FLOW3\Cache\CacheFactory $cacheFactory
	 * @return void
	 */
	public function injectCacheFactory(\TYPO3\FLOW3\Cache\CacheFactory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
	}

	/**
	 * Sets configurations for caches. The key of each entry specifies the
	 * cache identifier and the value is an array of configuration options.
	 * Possible options are:
	 *
	 *   frontend
	 *   backend
	 *   backendOptions
	 *
	 * If one of the options is not specified, the default value is assumed.
	 * Existing cache configurations are preserved.
	 *
	 * @param array $cacheConfigurations The cache configurations to set
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setCacheConfigurations(array $cacheConfigurations) {
		foreach ($cacheConfigurations as $identifier => $configuration) {
			if (!is_array($configuration)) throw new \InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1231259656);
			$this->cacheConfigurations[$identifier] = $configuration;
		}
	}

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache The cache frontend to be registered
	 * @return void
	 * @throws \TYPO3\FLOW3\Cache\Exception\DuplicateIdentifierException if a cache with the given identifier has already been registered.
	 * @api
	 */
	public function registerCache(\TYPO3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$identifier = $cache->getIdentifier();
		if (isset($this->caches[$identifier])) {
			throw new \TYPO3\FLOW3\Cache\Exception\DuplicateIdentifierException('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		}
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return \TYPO3\FLOW3\Cache\Frontend\FrontendInterface The specified cache frontend
	 * @throws \TYPO3\FLOW3\Cache\Exception\NoSuchCacheException
	 * @api
	 */
	public function getCache($identifier) {
		if ($this->hasCache($identifier) === FALSE) {
			throw new \TYPO3\FLOW3\Cache\Exception\NoSuchCacheException('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
		}
		if (!isset($this->caches[$identifier])) {
			$this->createCache($identifier);
		}
		return $this->caches[$identifier];
	}

	/**
	 * Checks if the specified cache has been registered.
	 *
	 * @param string $identifier The identifier of the cache
	 * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
	 * @api
	 */
	public function hasCache($identifier) {
		return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
	}

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @api
	 */
	public function flushCaches() {
		$this->createAllCaches();
		foreach ($this->caches as $cache) {
			$cache->flush();
		}
	}

	/**
	 * Flushes entries tagged by the specified tag of all registered
	 * caches.
	 *
	 * @param string $tag Tag to search for
	 * @return void
	 * @api
	 */
	public function flushCachesByTag($tag) {
		$this->createAllCaches();
		foreach ($this->caches as $cache) {
			$cache->flushByTag($tag);
		}
	}

	/**
	 * Flushes entries tagged with class names if their class source files have changed.
	 *
	 * This method is used as a slot for a signal sent by the class file monitor defined
	 * in the bootstrap.
	 *
	 * @param string $fileMonitorIdentifier Identifier of the File Monitor (must be "FLOW3_ClassFiles")
	 * @param array $changedFiles A list of full paths to changed files
	 * @return void
	 */
	public function flushClassFileCachesByChangedFiles($fileMonitorIdentifier, array $changedFiles) {
		if ($fileMonitorIdentifier !== 'FLOW3_ClassFiles') {
			return;
		}

		$this->flushCachesByTag(self::getClassTag());
		foreach ($changedFiles as $pathAndFilename => $status) {
			$pathAndFilename = str_replace(FLOW3_PATH_PACKAGES, '', $pathAndFilename);
			$matches = array();
			if (1 === preg_match('/[^\/]+\/(.+)\/(Classes|Tests)\/(.+)\.php/', $pathAndFilename, $matches)) {
				$className = str_replace('/', '\\', $matches[1] . '\\' . ($matches[2] === 'Tests' ? 'Tests\\' : '') . $matches[3]);
				$className = str_replace('.', '\\', $className);
				$this->flushCachesByTag(self::getClassTag($className));
			}
		}
	}

	/**
	 * Marks Doctrine proxy classes as outdated when Model classes have been changed
	 *
	 * This method is used as a slot for a signal sent by the class file monitor defined
	 * in the bootstrap.
	 *
	 * @param string $fileMonitorIdentifier Identifier of the File Monitor (must be "FLOW3_ClassFiles")
	 * @param array $changedFiles A list of full paths to changed files
	 * @return void
	 */
	public function markDoctrineProxyCodeOutdatedByChangedFiles($fileMonitorIdentifier, array $changedFiles) {
		if ($fileMonitorIdentifier !== 'FLOW3_ClassFiles') {
			return;
		}

		foreach ($changedFiles as $pathAndFilename => $status) {
			if (1 === preg_match('/\/Domain\/Model\/(.+)\.php/', $pathAndFilename)) {
				$this->getCache('FLOW3_Object_Configuration')->remove('doctrineProxyCodeUpToDate');
				break;
			}
		}
	}

	/**
	 * Renders a tag which can be used to mark a cache entry as "depends on this class".
	 * Whenever the specified class is modified, all cache entries tagged with the
	 * class are flushed.
	 *
	 * If an empty string is specified as class name, the returned tag means
	 * "this cache entry becomes invalid if any of the known classes changes".
	 *
	 * @param string $className The class name
	 * @return string Class Tag
	 * @api
	 */
	static public function getClassTag($className = '') {
		return ($className === '') ? FrontendInterface::TAG_CLASS : FrontendInterface::TAG_CLASS . str_replace('\\', '_', $className);
	}

	/**
	 * Instantiates all registered caches.
	 *
	 * @return void
	 */
	protected function createAllCaches() {
		foreach (array_keys($this->cacheConfigurations) as $identifier) {
			if ($identifier !== 'Default' && !isset($this->caches[$identifier])) {
				$this->createCache($identifier);
			}
		}
	}

	/**
	 * Instantiates the cache for $identifier.
	 *
	 * @param string $identifier
	 * @return void
	 */
	protected function createCache($identifier) {
		$frontend = isset($this->cacheConfigurations[$identifier]['frontend']) ? $this->cacheConfigurations[$identifier]['frontend'] : $this->cacheConfigurations['Default']['frontend'];
		$backend = isset($this->cacheConfigurations[$identifier]['backend']) ? $this->cacheConfigurations[$identifier]['backend'] : $this->cacheConfigurations['Default']['backend'];
		$backendOptions = isset($this->cacheConfigurations[$identifier]['backendOptions']) ? $this->cacheConfigurations[$identifier]['backendOptions'] : $this->cacheConfigurations['Default']['backendOptions'];
		$this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions);
	}
}
?>
