<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

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
 * The Resource Manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Manager {

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader Instance of the class loader
	 */
	protected $classLoader;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Resource\Publisher
	 */
	protected $resourcePublisher;

	/**
	 * @var \F3\FLOW3\Cache\Manager
	 */
	protected $cacheManager;

	/**
	 * @var array The loaded resources (identity map)
	 */
	protected $loadedResources = array();

	/**
	 * Constructs the resource manager
	 *
	 * @param array $settings
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the cache manager
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the cache manager
	 *
	 * @param \F3\FLOW3\Cache\Manager $cacheManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectCacheManager(\F3\FLOW3\Cache\Manager $cacheManager) {
		$this->cacheManager = $cacheManager;
	}

	/**
	 * Injects the resource publisher
	 *
	 * @param \F3\FLOW3\Resource\Publisher $resourcePublisher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectResourcePublisher(\F3\FLOW3\Resource\Publisher $resourcePublisher) {
		$this->resourcePublisher = $resourcePublisher;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Check for implementations of F3\FLOW3\Resource\StreamWrapperInterface and
	 * register them.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeStreamWrappers() {
		\F3\FLOW3\Resource\StreamWrapperAdapter::setObjectFactory($this->objectFactory);
		$streamWrapperClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Resource\StreamWrapperInterface');
		foreach ($streamWrapperClassNames as $streamWrapperClassName) {
			$scheme = $streamWrapperClassName::getScheme();
			if (in_array($scheme, stream_get_wrappers())) {
				stream_wrapper_unregister($scheme);
			}
			stream_wrapper_register($scheme, '\F3\FLOW3\Resource\StreamWrapperAdapter');
			\F3\FLOW3\Resource\StreamWrapperAdapter::registerStreamWrapper($scheme, $streamWrapperClassName);
		}
	}

	/**
	 * Prepares a mirror of public package resources that is accessible through
	 * the web server directly.
	 *
	 * @param array $activePackages
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function preparePublicPackageResourcesForWebAccess(array $activePackages) {
		$this->resourcePublisher->setMirrorStatusCache($this->cacheManager->getCache('FLOW3_Resource_Status'));
		$this->resourcePublisher->setMirrorDirectory($this->settings['cache']['publicPath']);
		$this->resourcePublisher->setMirrorStrategy($this->settings['cache']['strategy']);
		$this->resourcePublisher->setMirrorMode($this->settings['publisher']['mirrorMode']);

		foreach ($activePackages as $packageKey => $package) {
			$this->resourcePublisher->mirrorResources($package->getResourcesPath() . 'Public/', 'Packages/' . $packageKey . '/');
		}
	}

}

?>