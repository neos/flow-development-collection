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
use TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException;

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

		$resultCache = new \TYPO3\Flow\Persistence\Doctrine\CacheAdapter();
		// must use ObjectManager in compile phase...
		$resultCache->setCache($this->objectManager->get('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Persistence_Doctrine_Results'));
		$config->setResultCacheImpl($resultCache);

		if (class_exists($this->settings['doctrine']['sqlLogger'])) {
			$config->setSQLLogger(new $this->settings['doctrine']['sqlLogger']());
		}

		$eventManager = $this->buildEventManager();

		$flowAnnotationDriver = $this->objectManager->get('TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver');
		$config->setMetadataDriverImpl($flowAnnotationDriver);

		$proxyDirectory = \TYPO3\Flow\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies'));
		\TYPO3\Flow\Utility\Files::createDirectoryRecursively($proxyDirectory);
		$config->setProxyDir($proxyDirectory);
		$config->setProxyNamespace('TYPO3\Flow\Persistence\Doctrine\Proxies');
		$config->setAutoGenerateProxyClasses(FALSE);

		$entityManager = \Doctrine\ORM\EntityManager::create($this->settings['backendOptions'], $config, $eventManager);
		$flowAnnotationDriver->setEntityManager($entityManager);

		\Doctrine\DBAL\Types\Type::addType('objectarray', 'TYPO3\Flow\Persistence\Doctrine\DataTypes\ObjectArray');

		return $entityManager;
	}

	/**
	 * Add configured event subscribers and listeners to the event manager
	 *
	 * @return \Doctrine\Common\EventManager
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 */
	protected function buildEventManager() {
		$eventManager = new \Doctrine\Common\EventManager();
		if (isset($this->settings['doctrine']['eventSubscribers']) && is_array($this->settings['doctrine']['eventSubscribers'])) {
			foreach ($this->settings['doctrine']['eventSubscribers'] as $subscriberClassName) {
				$subscriber = $this->objectManager->get($subscriberClassName);
				if (!$subscriber instanceof \Doctrine\Common\EventSubscriber) {
					throw new IllegalObjectTypeException('Doctrine eventSubscribers must extend class \Doctrine\Common\EventSubscriber, ' . $subscriberClassName . ' fails to do so.', 1366018193);
				}
				$eventManager->addEventSubscriber($subscriber);
			}
		}
		if (isset($this->settings['doctrine']['eventListeners']) && is_array($this->settings['doctrine']['eventListeners'])) {
			foreach ($this->settings['doctrine']['eventListeners'] as $listenerOptions) {
				$listener = $this->objectManager->get($listenerOptions['listener']);
				$eventManager->addEventListener($listenerOptions['events'], $listener);
			}
		}
		return $eventManager;
	}

}
