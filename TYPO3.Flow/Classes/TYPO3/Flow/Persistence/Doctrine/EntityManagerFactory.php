<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerFactory {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Injects the Flow settings, the persistence part is kept
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
	 */
	public function create() {
		$config = new \Doctrine\ORM\Configuration();
		$config->setClassMetadataFactoryName('TYPO3\Flow\Persistence\Doctrine\Mapping\ClassMetadataFactory');

		$cache = new \TYPO3\Flow\Persistence\Doctrine\CacheAdapter();
			// must use ObjectManager in compile phase...
		$cache->setCache($this->objectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Persistence_Doctrine'));
		$config->setMetadataCacheImpl($cache);
		$config->setQueryCacheImpl($cache);

		if (class_exists($this->settings['doctrine']['sqlLogger'])) {
			$config->setSQLLogger(new $this->settings['doctrine']['sqlLogger']());
		}

		$flowAnnotationDriver = $this->objectManager->get('TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver');
		$config->setMetadataDriverImpl($flowAnnotationDriver);

		$proxyDirectory = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies'));
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($proxyDirectory);
		$config->setProxyDir($proxyDirectory);
		$config->setProxyNamespace('TYPO3\Flow\Persistence\Doctrine\Proxies');
		$config->setAutoGenerateProxyClasses(FALSE);

		$entityManager = \Doctrine\ORM\EntityManager::create($this->settings['backendOptions'], $config);
		$flowAnnotationDriver->setEntityManager($entityManager);
		return $entityManager;
	}

}

?>