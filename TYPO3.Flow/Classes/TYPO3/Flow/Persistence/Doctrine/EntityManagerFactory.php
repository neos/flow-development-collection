<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Configuration;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerFactory
{
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
     * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings['persistence'];
        if (!isset($this->settings['doctrine'])) {
            throw new \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException('The configuration TYPO3.Flow.persistence.doctrine is NULL, please check your settings.', 1392800005);
        }
    }

    /**
     * Factory method which creates an EntityManager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function create()
    {
        $config = new Configuration();
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
        $config->setAutoGenerateProxyClasses(false);

        // Set default host to 127.0.0.1 if there is no other host configured and at least one other necessary option
        if ($this->settings['backendOptions']['host'] == '') {
            if (
                $this->settings['backendOptions']['dbname'] != '' ||
                $this->settings['backendOptions']['user'] != '' ||
                $this->settings['backendOptions']['password'] != ''
            ) {
                $this->settings['backendOptions']['host'] = '127.0.0.1';
            }
        }

        // The following code tries to connect first, if that succeeds, all is well. If not, the platform is fetched directly from the
        // driver - without version checks to the database server (to which no connection can be made) - and is added to the config
        // which is then used to create a new connection. This connection will then return the platform directly, without trying to
        // detect the version it runs on, which fails if no connection can be made. But the platform is used even if no connection can
        // be made, which was no problem with Doctrine DBAL 2.3. And then came version-aware drivers and platforms...
        $connection = \Doctrine\DBAL\DriverManager::getConnection($this->settings['backendOptions'], $config, $eventManager);
        try {
            $connection->connect();
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            $settings = $this->settings['backendOptions'];
            $settings['platform'] = $connection->getDriver()->getDatabasePlatform();
            $connection = \Doctrine\DBAL\DriverManager::getConnection($settings, $config, $eventManager);
        }

        $entityManager = \Doctrine\ORM\EntityManager::create($connection, $config, $eventManager);
        $flowAnnotationDriver->setEntityManager($entityManager);

        \Doctrine\DBAL\Types\Type::addType('objectarray', 'TYPO3\Flow\Persistence\Doctrine\DataTypes\ObjectArray');

        if (isset($this->settings['doctrine']['filters']) && is_array($this->settings['doctrine']['filters'])) {
            foreach ($this->settings['doctrine']['filters'] as $filterName => $filterClass) {
                $config->addFilter($filterName, $filterClass);
                $entityManager->getFilters()->enable($filterName);
            }
        }

        if (isset($this->settings['doctrine']['dql']) && is_array($this->settings['doctrine']['dql'])) {
            $this->applyDqlSettingsToConfiguration($this->settings['doctrine']['dql'], $config);
        }

        return $entityManager;
    }

    /**
     * Add configured event subscribers and listeners to the event manager
     *
     * @return \Doctrine\Common\EventManager
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    protected function buildEventManager()
    {
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

    /**
     * Apply configured settings regarding DQL to the Doctrine Configuration.
     * At the moment, these are custom DQL functions.
     *
     * @param array $configuredSettings
     * @param Configuration $doctrineConfiguration
     * @return void
     */
    protected function applyDqlSettingsToConfiguration(array $configuredSettings, Configuration $doctrineConfiguration)
    {
        if (isset($configuredSettings['customStringFunctions'])) {
            $doctrineConfiguration->setCustomStringFunctions($configuredSettings['customStringFunctions']);
        }
        if (isset($configuredSettings['customNumericFunctions'])) {
            $doctrineConfiguration->setCustomNumericFunctions($configuredSettings['customNumericFunctions']);
        }
        if (isset($configuredSettings['customDatetimeFunctions'])) {
            $doctrineConfiguration->setCustomDatetimeFunctions($configuredSettings['customDatetimeFunctions']);
        }
    }
}
