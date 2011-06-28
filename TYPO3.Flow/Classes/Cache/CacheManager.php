<?php
namespace F3\FLOW3\Cache;

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

use \F3\FLOW3\Cache\Frontend\FrontendInterface;

/**
 * The Cache Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class CacheManager {

	/**
	 * @var \F3\FLOW3\Cache\CacheFactory
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
			'frontend' => 'F3\FLOW3\Cache\Frontend\VariableFrontend',
			'backend' =>  'F3\FLOW3\Cache\Backend\FileBackend',
			'backendOptions' => array()
		)
	);

	/**
	 * @param \F3\FLOW3\Cache\CacheFactory $cacheFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCacheFactory(\F3\FLOW3\Cache\CacheFactory $cacheFactory) {
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @param \F3\FLOW3\Cache\Frontend\FrontendInterface $cache The cache frontend to be registered
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception\DuplicateIdentifierException if a cache with the given identifier has already been registered.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function registerCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$identifier = $cache->getIdentifier();
		if (isset($this->caches[$identifier])) {
			throw new \F3\FLOW3\Cache\Exception\DuplicateIdentifierException('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		}
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return \F3\FLOW3\Cache\Frontend\FrontendInterface The specified cache frontend
	 * @throws \F3\FLOW3\Cache\Exception\NoSuchCacheException
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCache($identifier) {
		if ($this->hasCache($identifier) === FALSE) {
			throw new \F3\FLOW3\Cache\Exception\NoSuchCacheException('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function hasCache($identifier) {
		return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
	}

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	static public function getClassTag($className = '') {
		return ($className === '') ? FrontendInterface::TAG_CLASS : FrontendInterface::TAG_CLASS . str_replace('\\', '_', $className);
	}

	/**
	 * Instantiates all registered caches.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createCache($identifier) {
		$frontend = isset($this->cacheConfigurations[$identifier]['frontend']) ? $this->cacheConfigurations[$identifier]['frontend'] : $this->cacheConfigurations['Default']['frontend'];
		$backend = isset($this->cacheConfigurations[$identifier]['backend']) ? $this->cacheConfigurations[$identifier]['backend'] : $this->cacheConfigurations['Default']['backend'];
		$backendOptions = isset($this->cacheConfigurations[$identifier]['backendOptions']) ? $this->cacheConfigurations[$identifier]['backendOptions'] : $this->cacheConfigurations['Default']['backendOptions'];
		$this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions);
	}
}
?>
