<?php
declare(ENCODING = 'utf-8');
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

/**
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 */

/**
 * The Cache Manager
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Manager {

	/**
	 * @const Cache Entry depends on the PHP code of the packages
	 */
	const TAG_PACKAGES_CODE = '%PACKAGES_CODE%';

	/**
	 * @var \F3\FLOW3\Cache\Factory
	 */
	protected $cacheFactory;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array Registered Caches
	 */
	protected $caches = array();

	protected $cacheConfigurations = array(
		'Default' => array(
			'frontend' => 'F3\FLOW3\Cache\Frontend\VariableFrontend',
			'backend' =>  'F3\FLOW3\Cache\Backend\FileBackend',
			'backendOptions' => array()
		)
	);

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
	 * Injects the cache factory
	 *
	 * @param \F3\FLOW3\Cache\Factory $cacheFactory The cache factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCacheFactory(\F3\FLOW3\Cache\Factory $cacheFactory) {
		$this->cacheFactory = $cacheFactory;
		$this->cacheFactory->setCacheManager($this);
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the cache manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		foreach ($this->cacheConfigurations as $identifier => $configuration) {
			if ($identifier !== 'Default') {
				$frontend = isset($configuration['frontend']) ? $configuration['frontend'] : $this->cacheConfigurations['Default']['frontend'];
				$backend = isset($configuration['backend']) ? $configuration['backend'] : $this->cacheConfigurations['Default']['backend'];
				$backendOptions = isset($configuration['backendOptions']) ? $configuration['backendOptions'] : $this->cacheConfigurations['Default']['backendOptions'];
				$cache = $this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions);
			}
		}
	}

	/**
	 * Registers a cache so it can be retrieved at a later point.
	 *
	 * @param \F3\FLOW3\Cache\Frontend\FrontendInterface $cache The cache frontend to be registered
	 * @return void
	 * @throws \F3\FLOW3\Cache\Exception\DuplicateIdentifier if a cache with the given identifier has already been registered.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerCache(\F3\FLOW3\Cache\Frontend\FrontendInterface $cache) {
		$identifier = $cache->getIdentifier();
		if (isset($this->caches[$identifier])) throw new \F3\FLOW3\Cache\Exception\DuplicateIdentifier('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
		$this->caches[$identifier] = $cache;
	}

	/**
	 * Returns the cache specified by $identifier
	 *
	 * @param string $identifier Identifies which cache to return
	 * @return \F3\FLOW3\Cache\Frontend\FrontendInterface The specified cache frontend
	 * @throws \F3\FLOW3\Cache\Exception\NoSuchCache
	 */
	public function getCache($identifier) {
		if (!isset($this->caches[$identifier])) throw new \F3\FLOW3\Cache\Exception\NoSuchCache('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
		return $this->caches[$identifier];
	}

	/**
	 * Checks if the specified cache has been registered.
	 *
	 * @param string $identifier The identifier of the cache
	 * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasCache($identifier) {
		return isset($this->caches[$identifier]);
	}

	/**
	 * Flushes all registered caches
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushCaches() {
		$this->systemLogger->log('Flushing all registered caches.', LOG_NOTICE);
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
	 */
	public function flushCachesByTag($tag) {
		$this->systemLogger->log(sprintf('Flushing caches by tag "%s".', $tag), LOG_NOTICE);
		foreach ($this->caches as $cache) {
			$cache->flushByTag($tag);
		}
	}
}
?>