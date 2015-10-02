<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\BaseAnnotationDriver;
use TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\Flow\Utility\Files;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class BaseEntityManagerFactory
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Factory method which creates an EntityManager.
     *
     * @param array $settings
     * @return EntityManager
     * @throws InvalidConfigurationException
     */
    public function create(array $settings = [])
    {
        if (!isset($settings['doctrine'])) {
            throw new InvalidConfigurationException('The given configuration does not contain a key "doctrine", please check your settings.', 1405530366);
        }

        $configuration = $this->createDoctrineConfiguration($settings);
        $metadataDriver = $this->getAnnotationDriver($settings);
        $configuration->setMetadataDriverImpl($metadataDriver);
        $eventManager = $this->buildEventManager($settings);

        // The following code tries to connect first, if that succeeds, all is well. If not, the platform is fetched directly from the
        // driver - without version checks to the database server (to which no connection can be made) - and is added to the config
        // which is then used to create a new connection. This connection will then return the platform directly, without trying to
        // detect the version it runs on, which fails if no connection can be made. But the platform is used even if no connection can
        // be made, which was no problem with Doctrine DBAL 2.3. And then came version-aware drivers and platforms...
        $backendOptions = $settings['backendOptions'];
        $connection = DriverManager::getConnection($backendOptions, $configuration, $eventManager);
        try {
            $connection->connect();
        } catch (ConnectionException $exception) {
            $backendOptions['platform'] = $connection->getDriver()->getDatabasePlatform();
            $connection = DriverManager::getConnection($backendOptions, $configuration, $eventManager);
        }

        $entityManager = EntityManager::create($connection, $configuration, $eventManager);
        $metadataDriver->setEntityManager($entityManager);

        $this->addDoctrineTypes($entityManager, $settings);
        $this->addDoctrineFilters($configuration, $entityManager, $settings);

        if (isset($settings['doctrine']['dql']) && is_array($settings['doctrine']['dql'])) {
            $this->applyDqlSettingsToConfiguration($settings['doctrine']['dql'], $configuration);
        }

        return $entityManager;
    }

    /**
     * @param array $settings
     * @return Configuration
     */
    protected function createDoctrineConfiguration(array $settings)
    {
        $doctrineConfiguration = new Configuration();
        $doctrineConfiguration->setClassMetadataFactoryName(Mapping\ClassMetadataFactory::class);

        $genericCache = $this->getGenericCache($settings);
        if ($genericCache !== null) {
            $doctrineConfiguration->setMetadataCacheImpl($genericCache);
            $doctrineConfiguration->setQueryCacheImpl($genericCache);
        }

        $resulteCache = $this->getResultsCache($settings);
        if ($resulteCache !== null) {
            $doctrineConfiguration->setResultCacheImpl($resulteCache);
        }

        $sqlLoggerInstance = $this->getLogger($settings);
        if ($sqlLoggerInstance !== null) {
            $doctrineConfiguration->setSQLLogger($sqlLoggerInstance);
        }

        $this->setProxyPath($doctrineConfiguration, $settings);

        return $doctrineConfiguration;
    }

    /**
     * @param array $settings
     * @return CacheAdapter
     */
    protected function getGenericCache(array $settings)
    {
        $cache = null;
        if (isset($settings['doctrine']['genericCacheName'])) {
            $cache = new CacheAdapter();
            // must use ObjectManager in compile phase...
            $cache->setCache($this->objectManager->get(CacheManager::class)->getCache($settings['doctrine']['genericCacheName']));
        }

        return $cache;
    }

    /**
     * @param array $settings
     * @return CacheAdapter
     */
    protected function getResultsCache(array $settings)
    {
        $cache = null;
        if (isset($settings['doctrine']['resultCacheName'])) {
            $cache = new CacheAdapter();
            // must use ObjectManager in compile phase...
            $cache->setCache($this->objectManager->get(CacheManager::class)->getCache($settings['doctrine']['resultCacheName']));
        }

        return $cache;
    }

    /**
     * @param array $settings
     * @return null
     * @throws InvalidConfigurationException
     */
    protected function getLogger(array $settings)
    {
        $sqlLoggerInstance = null;
        $loggerClassName = isset($settings['doctrine']['sqlLogger']) && is_string($settings['doctrine']['sqlLogger']) ? $settings['doctrine']['sqlLogger'] : '';
        if (class_exists($loggerClassName)) {
            $sqlLoggerInstance = new $loggerClassName();
        }

        if (isset($sqlLoggerInstance) && !$sqlLoggerInstance instanceof SQLLogger) {
            throw new InvalidConfigurationException(sprintf('TYPO3.Flow.persistence.doctrine.sqlLogger must point to a \Doctrine\DBAL\Logging\SQLLogger implementation, %s given.', get_class($sqlLoggerInstance)), 1426150388);
        }

        return $sqlLoggerInstance;
    }

    /**
     * Get the annotation driver responsible for this connection.
     *
     * @param array $settings
     * @return BaseAnnotationDriver
     */
    protected function getAnnotationDriver(array $settings = [])
    {
        return $this->objectManager->get(BaseAnnotationDriver::class);
    }

    /**
     * This adds custom types to doctrines type map.
     * WARNING the type map is globally shared, so don't add the same type twice.
     *
     * @param EntityManager $entityManager
     * @param array $settings
     * @throws \Doctrine\DBAL\DBALException
     * @see EntityManagerFactory::addDoctrineTypes()
     */
    protected function addDoctrineTypes(EntityManager $entityManager, array $settings)
    {
        if (!isset($settings['doctrine']['dbal']['mappingTypes']) || !is_array($settings['doctrine']['dbal']['mappingTypes'])) {
            return;
        }

        foreach ($settings['doctrine']['dbal']['mappingTypes'] as $typeName => $typeConfiguration) {
            Type::addType($typeName, $typeConfiguration['className']);
            $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
        }
    }

    /**
     * Add configured filters to doctrine
     *
     * @param Configuration $doctrineConfiguration
     * @param EntityManager $entityManager
     * @param array $settings
     * @return void
     */
    protected function addDoctrineFilters(Configuration $doctrineConfiguration, EntityManager $entityManager, array $settings)
    {
        if (!isset($settings['doctrine']['filters']) || !is_array($settings['doctrine']['filters'])) {
            return;
        }

        foreach ($settings['doctrine']['filters'] as $filterName => $filterClass) {
            $doctrineConfiguration->addFilter($filterName, $filterClass);
            $entityManager->getFilters()->enable($filterName);
        }
    }

    /**
     * @param Configuration $doctrineConfiguration
     * @param array $settings
     * @return void
     */
    protected function setProxyPath(Configuration $doctrineConfiguration, $settings)
    {
        $proxyDirectory = $settings['doctrine']['proxyPath'];

        Files::createDirectoryRecursively($proxyDirectory);
        $doctrineConfiguration->setProxyDir($proxyDirectory);
        $doctrineConfiguration->setProxyNamespace('TYPO3\Flow\Persistence\Doctrine\Proxies');
        $doctrineConfiguration->setAutoGenerateProxyClasses(false);
    }

    /**
     * Build the EventManager and adds configured event subscribers and listeners to it.
     *
     * @param array $settings
     * @return EventManager
     */
    protected function buildEventManager(array $settings)
    {
        $eventManager = new EventManager();
        if (isset($settings['doctrine']['eventSubscribers']) && is_array($settings['doctrine']['eventSubscribers'])) {
            $this->addEventSubscribersToEventManager($eventManager, $settings['doctrine']['eventSubscribers']);
        }
        if (isset($settings['doctrine']['eventListeners']) && is_array($settings['doctrine']['eventListeners'])) {
            $this->addEventListenersToEventManager($eventManager, $settings['doctrine']['eventListeners']);
        }

        return $eventManager;
    }

    /**
     * Adds given event subscribers to the EventManager.
     *
     * @param EventManager $eventManager
     * @param $configuredSubscribers
     * @return void
     * @throws IllegalObjectTypeException
     */
    protected function addEventSubscribersToEventManager(EventManager $eventManager, $configuredSubscribers)
    {
        foreach ($configuredSubscribers as $subscriberClassName) {
            $subscriber = $this->objectManager->get($subscriberClassName);
            if (!$subscriber instanceof EventSubscriber) {
                throw new IllegalObjectTypeException('Doctrine eventSubscribers must extend class \Doctrine\Common\EventSubscriber, ' . $subscriberClassName . ' fails to do so.', 1366018193);
            }
            $eventManager->addEventSubscriber($subscriber);
        }
    }

    /**
     * Adds given event listeners to the EventManager.
     *
     * @param EventManager $eventManager
     * @param $configuredListeners
     * @return void
     */
    protected function addEventListenersToEventManager(EventManager $eventManager, $configuredListeners)
    {
        foreach ($configuredListeners as $listenerOptions) {
            $listener = $this->objectManager->get($listenerOptions['listener']);
            $eventManager->addEventListener($listenerOptions['events'], $listener);
        }
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
