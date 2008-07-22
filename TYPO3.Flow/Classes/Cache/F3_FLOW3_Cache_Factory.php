<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @version $Id:F3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Cache_Factory {

	/**
	 * A reference to the component manager
	 *
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * A reference to the component factory
	 *
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * A reference to the cache manager
	 *
	 * @var F3_FLOW3_Cache_Manager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager A reference to the component manager
	 * @param F3_FLOW3_Component_ManagerInterface $componentFactory A reference to the component factory
	 * 	 * @param F3_FLOW3_Cache_Manager $cacheManager A reference to the cache manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_FLOW3_Component_FactoryInterface $componentFactory, F3_FLOW3_Cache_Manager $cacheManager) {
		$this->componentManager = $componentManager;
		$this->componentFactory = $componentFactory;
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheComponentName Component name of the cache frontend
	 * @param string $backendComponentName Component name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return F3_FLOW3_Cache_AbstractCache The created cache frontend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($cacheIdentifier, $cacheComponentName, $backendComponentName, array $backendOptions = array()) {
		$context = $this->componentManager->getContext();
		$backend = $this->componentFactory->getComponent($backendComponentName, $context, $backendOptions);
		if (!$backend instanceof F3_FLOW3_Cache_AbstractBackend) throw new F3_FLOW3_Cache_Exception_InvalidBackend('"' .$backendComponentName . '" is not a valid cache backend component.', 1216304301);
		$cache = $this->componentFactory->getComponent($cacheComponentName, $cacheIdentifier, $backend);
		if (!$cache instanceof F3_FLOW3_Cache_AbstractCache) throw new F3_FLOW3_Cache_Exception_InvalidCache('"' . $cacheComponentName . '" is not a valid cache component.', 1216304300);

		$this->cacheManager->registerCache($cache);
		return $cache;
	}

}
?>