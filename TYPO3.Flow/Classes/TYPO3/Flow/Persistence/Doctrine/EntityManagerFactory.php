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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Environment;
use TYPO3\Flow\Utility\Files;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerFactory
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

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
    public function injectSettings(array $settings)
    {
        $this->settings = $settings['persistence'];
        if (!is_array($this->settings['doctrine'])) {
            throw new InvalidConfigurationException(sprintf('The TYPO3.Flow.persistence.doctrine settings need to be an array, %s given.', gettype($this->settings['doctrine'])), 1392800005);
        }
        if (!is_array($this->settings['backendOptions'])) {
            throw new InvalidConfigurationException(sprintf('The TYPO3.Flow.persistence.backendOptions settings need to be an array, %s given.', gettype($this->settings['backendOptions'])), 1426149224);
        }
    }

    /**
     * Factory method which creates an EntityManager.
     * @return EntityManager
     * @throws InvalidConfigurationException
     */
    public function create()
    {
        $config = new Configuration();
        $config->setClassMetadataFactoryName(Mapping\ClassMetadataFactory::class);

        $cache = new CacheAdapter();
        // must use ObjectManager in compile phase...
        $cache->setCache($this->objectManager->get(CacheManager::class)->getCache('Flow_Persistence_Doctrine'));
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $resultCache = new CacheAdapter();
        // must use ObjectManager in compile phase...
        $resultCache->setCache($this->objectManager->get(CacheManager::class)->getCache('Flow_Persistence_Doctrine_Results'));
        $config->setResultCacheImpl($resultCache);

        if (is_string($this->settings['doctrine']['sqlLogger']) && class_exists($this->settings['doctrine']['sqlLogger'])) {
            $sqlLoggerInstance = new $this->settings['doctrine']['sqlLogger']();
            if ($sqlLoggerInstance instanceof SQLLogger) {
                $config->setSQLLogger($sqlLoggerInstance);
            } else {
                throw new InvalidConfigurationException(sprintf('TYPO3.Flow.persistence.doctrine.sqlLogger must point to a \Doctrine\DBAL\Logging\SQLLogger implementation, %s given.', get_class($sqlLoggerInstance)), 1426150388);
            }
        }

        $eventManager = $this->buildEventManager();

        $flowAnnotationDriver = $this->objectManager->get(FlowAnnotationDriver::class);
        $config->setMetadataDriverImpl($flowAnnotationDriver);

        $proxyDirectory = Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies']);
        Files::createDirectoryRecursively($proxyDirectory);
        $config->setProxyDir($proxyDirectory);
        $config->setProxyNamespace(Proxies::class);
        $config->setAutoGenerateProxyClasses(false);

        // The following code tries to connect first, if that succeeds, all is well. If not, the platform is fetched directly from the
        // driver - without version checks to the database server (to which no connection can be made) - and is added to the config
        // which is then used to create a new connection. This connection will then return the platform directly, without trying to
        // detect the version it runs on, which fails if no connection can be made. But the platform is used even if no connection can
        // be made, which was no problem with Doctrine DBAL 2.3. And then came version-aware drivers and platforms...
        $connection = DriverManager::getConnection($this->settings['backendOptions'], $config, $eventManager);
        try {
            $connection->connect();
        } catch (ConnectionException $e) {
            $settings = $this->settings['backendOptions'];
            $settings['platform'] = $connection->getDriver()->getDatabasePlatform();
            $connection = DriverManager::getConnection($settings, $config, $eventManager);
        }

        $entityManager = EntityManager::create($connection, $config, $eventManager);
        $flowAnnotationDriver->setEntityManager($entityManager);

        if (isset($this->settings['doctrine']['dbal']['mappingTypes']) && is_array($this->settings['doctrine']['dbal']['mappingTypes'])) {
            foreach ($this->settings['doctrine']['dbal']['mappingTypes'] as $typeName => $typeConfiguration) {
                Type::addType($typeName, $typeConfiguration['className']);
                $entityManager->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($typeConfiguration['dbType'], $typeName);
            }
        }

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
     * @return EventManager
     * @throws IllegalObjectTypeException
     */
    protected function buildEventManager()
    {
        $eventManager = new EventManager();
        if (isset($this->settings['doctrine']['eventSubscribers']) && is_array($this->settings['doctrine']['eventSubscribers'])) {
            foreach ($this->settings['doctrine']['eventSubscribers'] as $subscriberClassName) {
                $subscriber = $this->objectManager->get($subscriberClassName);
                if (!$subscriber instanceof EventSubscriber) {
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
