<?php

namespace Neos\Flow\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\Doctrine\Logging\SqlLogger;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Annotations as Flow;

/**
 * EntityManager configuration handler
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerConfiguration
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Injects the Flow settings, the persistence part is kept
     * for further use.
     *
     * @param array $settings
     * @return void
     * @throws InvalidConfigurationException
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings['persistence'];
        if (!is_array($this->settings['doctrine'])) {
            throw new InvalidConfigurationException(sprintf('The Neos.Flow.persistence.doctrine settings need to be an array, %s given.', gettype($this->settings['doctrine'])), 1392800005);
        }
    }

    /**
     * Configure the Doctrine EntityManager according to configuration settings before it's creation.
     *
     * @param Connection $connection
     * @param Configuration $config
     * @param EventManager $eventManager
     * @throws InvalidConfigurationException
     * @throws IllegalObjectTypeException
     */
    public function configureEntityManager(Connection $connection, Configuration $config, EventManager $eventManager): void
    {
        if (isset($this->settings['doctrine']['sqlLogger']) && is_string($this->settings['doctrine']['sqlLogger']) && class_exists($this->settings['doctrine']['sqlLogger'])) {
            $this->enableSqlLogger($this->settings['doctrine']['sqlLogger'], $config);
        }

        if (isset($this->settings['doctrine']['eventSubscribers']) && is_array($this->settings['doctrine']['eventSubscribers'])) {
            $this->registerEventSubscribers($this->settings['doctrine']['eventSubscribers'], $eventManager);
        }

        if (isset($this->settings['doctrine']['eventListeners']) && is_array($this->settings['doctrine']['eventListeners'])) {
            $this->registerEventListeners($this->settings['doctrine']['eventListeners'], $eventManager);
        }

        $this->applyCacheConfiguration($config);

        if (isset($this->settings['doctrine']['secondLevelCache']) && is_array($this->settings['doctrine']['secondLevelCache'])) {
            $this->applySecondLevelCacheSettingsToConfiguration($this->settings['doctrine']['secondLevelCache'], $config);
        }

        if (isset($this->settings['doctrine']['dql']) && is_array($this->settings['doctrine']['dql'])) {
            $this->applyDqlSettingsToConfiguration($this->settings['doctrine']['dql'], $config);
        }
    }

    /**
     * @param string $configuredSqlLogger
     * @param Configuration $doctrineConfiguration
     * @throws InvalidConfigurationException
     */
    protected function enableSqlLogger(string $configuredSqlLogger, Configuration $doctrineConfiguration): void
    {
        $sqlLoggerInstance = new $configuredSqlLogger();
        if ($sqlLoggerInstance instanceof SQLLogger) {
            $doctrineConfiguration->setSQLLogger($sqlLoggerInstance);
        } else {
            throw new InvalidConfigurationException(sprintf('Neos.Flow.persistence.doctrine.sqlLogger must point to a \Doctrine\DBAL\Logging\SQLLogger implementation, %s given.', get_class($sqlLoggerInstance)), 1426150388);
        }
    }

    /**
     * @param array $configuredSubscribers
     * @param EventManager $eventManager
     * @throws IllegalObjectTypeException
     */
    protected function registerEventSubscribers(array $configuredSubscribers, EventManager $eventManager): void
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
     * @param array $configuredListeners
     * @param EventManager $eventManager
     */
    protected function registerEventListeners(array $configuredListeners, EventManager $eventManager): void
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
    protected function applyDqlSettingsToConfiguration(array $configuredSettings, Configuration $doctrineConfiguration): void
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

    /**
     * Apply basic cache configuration for the metadata, query and result caches.
     *
     * @param Configuration $config
     * @throws NoSuchCacheException
     */
    protected function applyCacheConfiguration(Configuration $config): void
    {
        $cache = new CacheAdapter();
        // must use ObjectManager in compile phase...
        $cache->setCache($this->objectManager->get(CacheManager::class)->getCache('Flow_Persistence_Doctrine'));
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $resultCache = new CacheAdapter();
        // must use ObjectManager in compile phase...
        $resultCache->setCache($this->objectManager->get(CacheManager::class)->getCache('Flow_Persistence_Doctrine_Results'));
        $config->setResultCacheImpl($resultCache);
    }

    /**
     * Apply configured settings regarding Doctrine's second level cache.
     *
     * @param array $configuredSettings
     * @param Configuration $doctrineConfiguration
     * @return void
     * @throws NoSuchCacheException
     */
    protected function applySecondLevelCacheSettingsToConfiguration(array $configuredSettings, Configuration $doctrineConfiguration): void
    {
        if (!isset($configuredSettings['enable']) || $configuredSettings['enable'] !== true) {
            return;
        }

        $doctrineConfiguration->setSecondLevelCacheEnabled();
        $regionsConfiguration = $doctrineConfiguration->getSecondLevelCacheConfiguration()->getRegionsConfiguration();
        if (isset($configuredSettings['defaultLifetime'])) {
            $regionsConfiguration->setDefaultLifetime($configuredSettings['defaultLifetime']);
        }
        if (isset($configuredSettings['defaultLockLifetime'])) {
            $regionsConfiguration->setDefaultLockLifetime($configuredSettings['defaultLockLifetime']);
        }

        if (isset($configuredSettings['regions']) && is_array($configuredSettings['regions'])) {
            foreach ($configuredSettings['regions'] as $regionName => $regionLifetime) {
                $regionsConfiguration->setLifetime($regionName, $regionLifetime);
            }
        }

        $cache = new CacheAdapter();
        // must use ObjectManager in compile phase...
        $cache->setCache($this->objectManager->get(CacheManager::class)->getCache('Flow_Persistence_Doctrine_SecondLevel'));

        $factory = new DefaultCacheFactory($regionsConfiguration, $cache);
        $doctrineConfiguration->getSecondLevelCacheConfiguration()->setCacheFactory($factory);
    }

    /**
     * Enhance the Doctrine EntityManager by applying post creation settings, like custom filters.
     *
     * @param Configuration $config
     * @param EntityManager $entityManager
     * @throws DBALException
     */
    public function enhanceEntityManager(Configuration $config, EntityManager $entityManager): void
    {
        if (isset($this->settings['doctrine']['dbal']['mappingTypes']) && is_array($this->settings['doctrine']['dbal']['mappingTypes'])) {
            foreach ($this->settings['doctrine']['dbal']['mappingTypes'] as $typeName => $typeConfiguration) {
                if (!Type::hasType($typeName)) {
                    Type::addType($typeName, $typeConfiguration['className']);
                }
                $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
            }
        }

        if (isset($this->settings['doctrine']['filters']) && is_array($this->settings['doctrine']['filters'])) {
            foreach ($this->settings['doctrine']['filters'] as $filterName => $filterClass) {
                $config->addFilter($filterName, $filterClass);
                $entityManager->getFilters()->enable($filterName);
            }
        }
    }
}
