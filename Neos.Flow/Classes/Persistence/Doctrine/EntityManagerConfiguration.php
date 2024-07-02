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
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package;
use Neos\Flow\Persistence\Doctrine\Logging\SqlLogger;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Annotations as Flow;
use Psr\Cache\CacheItemPoolInterface;

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
    protected ObjectManagerInterface $objectManager;

    /**
     * @var array{"doctrine": array<string, mixed>}
     */
    protected array $settings;

    /**
     * Injects the Flow settings, the persistence part is kept
     * for further use.
     *
     * @param array<string, mixed> $settings
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
     * Configure the Doctrine EntityManager according to configuration settings before its creation.
     *
     * Note that this is called via SignalSlot in {@see Package} and therefore the arguments are
     * defined by what beforeDoctrineEntityManagerCreation provides (leaving the first argument unused here).
     *
     * @param Connection $connection
     * @param Configuration $config
     * @param EventManager $eventManager
     * @throws InvalidConfigurationException
     * @throws IllegalObjectTypeException
     * @throws NoSuchCacheException
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
        // Here we do not use the wrapper as below as the metadata cannot change at runtime anyway.
        $cache = $this->objectManager->get(CacheManager::class)->getCacheItemPool('Flow_Persistence_Doctrine_Metadata');
        $config->setMetadataCache($cache);

        /**
         * FIXME:
         * We shouldn't need this wrapper adding the security hash as {@see SqlFilter::addFilterConstraint} does that already,
         * and the parameters there are hashed into the query cache key in doctrines Query class.
         * But tests fail if it doesn't happen
         */
        $config->setQueryCache($this->getSecurityHashAwareCacheItemPool('Flow_Persistence_Doctrine'));

        $config->setResultCache($this->getSecurityHashAwareCacheItemPool('Flow_Persistence_Doctrine_Results'));
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
        $doctrineSecondLevelCacheConfiguration = $doctrineConfiguration->getSecondLevelCacheConfiguration();
        if ($doctrineSecondLevelCacheConfiguration === null) {
            throw new \RuntimeException('No doctrine second level cache configuration found.', 1719343602);
        }
        /** @var RegionsConfiguration $regionsConfiguration */
        $regionsConfiguration = $doctrineSecondLevelCacheConfiguration->getRegionsConfiguration();
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

        $factory = new DefaultCacheFactory(
            $regionsConfiguration,
            $this->getSecurityHashAwareCacheItemPool('Flow_Persistence_Doctrine_SecondLevel')
        );
        $doctrineSecondLevelCacheConfiguration->setCacheFactory($factory);
    }

    /**
     * Enhance the Doctrine EntityManager by applying post creation settings, like custom filters.
     *
     * @param Configuration $config
     * @param EntityManager $entityManager
     * @throws DbalException
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

    private function getSecurityHashAwareCacheItemPool(string $cacheIdentifier): CacheItemPoolInterface
    {
        $cache = $this->objectManager->get(CacheManager::class)->getCache($cacheIdentifier);
        return new CachePool($cacheIdentifier, $cache->getBackend());
    }
}
