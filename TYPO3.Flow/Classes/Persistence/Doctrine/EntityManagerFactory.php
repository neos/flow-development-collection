<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

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
 * EntityManager factory for Doctrine integration
 *
 * @scope singleton
 */
class EntityManagerFactory {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the FLOW3 settings, the persistence part is kept
	 * for further use.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings['persistence'];
	}

	/**
	 * Factory method which creates an EntityManager.
	 *
	 * @return \Doctrine\ORM\EntityManager
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function create() {
		$config = new \Doctrine\ORM\Configuration();
		$config->setClassMetadataFactoryName('TYPO3\FLOW3\Persistence\Doctrine\Mapping\ClassMetadataFactory');

		if (class_exists($this->settings['doctrine']['cacheImplementation'])) {
			$cache = new $this->settings['doctrine']['cacheImplementation']();
			$config->setMetadataCacheImpl($cache);
			$config->setQueryCacheImpl($cache);
		}

		if (class_exists($this->settings['doctrine']['sqlLogger'])) {
			$config->setSQLLogger(new $this->settings['doctrine']['sqlLogger']());
		}

			// must use ObjectManager in compile phase...
		$config->setMetadataDriverImpl($this->objectManager->get('TYPO3\FLOW3\Persistence\Doctrine\Mapping\Driver\Flow3AnnotationDriver'));

		$proxyDirectory = \TYPO3\FLOW3\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies'));
		\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($proxyDirectory);
		$config->setProxyDir($proxyDirectory);
		$config->setProxyNamespace('TYPO3\FLOW3\Persistence\Doctrine\Proxies');
		$config->setAutoGenerateProxyClasses(FALSE);

		return \Doctrine\ORM\EntityManager::create($this->settings['backendOptions'], $config);
	}

}

?>