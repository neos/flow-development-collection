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
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * @package FLOW3
 * @subpackage Cache
 * @version $Id:\F3\FLOW3\AOP\Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Factory {

	/**
	 * A reference to the object manager
	 *
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * A reference to the object factory
	 *
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * A reference to the cache manager
	 *
	 * @var \F3\FLOW3\Cache\Manager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @param \F3\FLOW3\Object\ManagerInterface $objectFactory A reference to the object factory
	 * @param \F3\FLOW3\Cache\Manager $cacheManager A reference to the cache manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager, \F3\FLOW3\Object\FactoryInterface $objectFactory, \F3\FLOW3\Cache\Manager $cacheManager) {
		$this->objectManager = $objectManager;
		$this->objectFactory = $objectFactory;
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheObjectName Object name of the cache frontend
	 * @param string $backendObjectName Object name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return \F3\FLOW3\Cache\AbstractCache The created cache frontend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {
		$context = $this->objectManager->getContext();
		$backend = $this->objectFactory->create($backendObjectName, $context, $backendOptions);
		if (!$backend instanceof \F3\FLOW3\Cache\AbstractBackend) throw new \F3\FLOW3\Cache\Exception\InvalidBackend('"' . $backendObjectName . '" is not a valid cache backend object.', 1216304301);
		$cache = $this->objectFactory->create($cacheObjectName, $cacheIdentifier, $backend);
		if (!$cache instanceof \F3\FLOW3\Cache\AbstractCache) throw new \F3\FLOW3\Cache\Exception\InvalidCache('"' . $cacheObjectName . '" is not a valid cache object.', 1216304300);

		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}
?>