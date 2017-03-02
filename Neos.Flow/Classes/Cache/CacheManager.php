<?php
namespace Neos\Flow\Cache;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\Backend\FileBackend;
use Neos\Cache\Exception\DuplicateIdentifierException;
use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;
use Neos\Flow\Utility\PhpAnalyzer;

/**
 * The Cache Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class CacheManager
{
    /**
     * @var CacheFactory
     */
    protected $cacheFactory;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected $caches = [];

    /**
     * @var array
     */
    protected $persistentCaches = [];

    /**
     * @var array
     */
    protected $cacheConfigurations = [
        'Default' => [
            'frontend' => VariableFrontend::class,
            'backend' => FileBackend::class,
            'backendOptions' => [],
            'persistent' => false
        ]
    ];

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * @param CacheFactory $cacheFactory
     * @return void
     */
    public function injectCacheFactory(CacheFactory $cacheFactory)
    {
        $this->cacheFactory = $cacheFactory;
    }

    /**
     * @param ConfigurationManager $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param Environment $environment
     * @return void
     */
    public function injectEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Sets configurations for caches. The key of each entry specifies the
     * cache identifier and the value is an array of configuration options.
     * Possible options are:
     *
     *   frontend
     *   backend
     *   backendOptions
     *   persistent
     *
     * If one of the options is not specified, the default value is assumed.
     * Existing cache configurations are preserved.
     *
     * @param array $cacheConfigurations The cache configurations to set
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setCacheConfigurations(array $cacheConfigurations)
    {
        foreach ($cacheConfigurations as $identifier => $configuration) {
            if (!is_array($configuration)) {
                throw new \InvalidArgumentException('The cache configuration for cache "' . $identifier . '" was not an array as expected.', 1231259656);
            }
            $this->cacheConfigurations[$identifier] = $configuration;
        }
    }

    /**
     * Registers a cache so it can be retrieved at a later point.
     *
     * @param FrontendInterface $cache The cache frontend to be registered
     * @param bool $persistent
     * @return void
     * @throws DuplicateIdentifierException if a cache with the given identifier has already been registered.
     * @api
     */
    public function registerCache(FrontendInterface $cache, $persistent = false)
    {
        $identifier = $cache->getIdentifier();
        if (isset($this->caches[$identifier])) {
            throw new DuplicateIdentifierException('A cache with identifier "' . $identifier . '" has already been registered.', 1203698223);
        }
        $this->caches[$identifier] = $cache;
        if ($persistent === true) {
            $this->persistentCaches[$identifier] = $cache;
        }
    }

    /**
     * Returns the cache specified by $identifier
     *
     * @param string $identifier Identifies which cache to return
     * @return \Neos\Cache\Frontend\FrontendInterface The specified cache frontend
     * @throws NoSuchCacheException
     * @api
     */
    public function getCache($identifier)
    {
        if ($this->hasCache($identifier) === false) {
            throw new NoSuchCacheException('A cache with identifier "' . $identifier . '" does not exist.', 1203699034);
        }
        if (!isset($this->caches[$identifier])) {
            $this->createCache($identifier);
        }

        return $this->caches[$identifier];
    }

    /**
     * Checks if the specified cache has been registered.
     *
     * @param string $identifier The identifier of the cache
     * @return boolean TRUE if a cache with the given identifier exists, otherwise FALSE
     * @api
     */
    public function hasCache($identifier)
    {
        return isset($this->caches[$identifier]) || isset($this->cacheConfigurations[$identifier]);
    }

    /**
     * Checks if the specified cache is marked as "persistent".
     *
     * @param string $identifier The identifier of the cache
     * @return boolean TRUE if the specified cache is persistent, FALSE if it is not, or if the cache does not exist
     */
    public function isCachePersistent($identifier)
    {
        return isset($this->persistentCaches[$identifier]);
    }

    /**
     * Flushes all registered caches
     *
     * @param boolean $flushPersistentCaches If set to TRUE, even those caches which are flagged as "persistent" will be flushed
     * @return void
     * @api
     */
    public function flushCaches($flushPersistentCaches = false)
    {
        $this->createAllCaches();
        /** @var FrontendInterface $cache */
        foreach ($this->caches as $identifier => $cache) {
            if (!$flushPersistentCaches && $this->isCachePersistent($identifier)) {
                continue;
            }
            $cache->flush();
        }
        $this->configurationManager->flushConfigurationCache();
        $dataTemporaryPath = $this->environment->getPathToTemporaryDirectory();
        Files::unlink($dataTemporaryPath . 'AvailableProxyClasses.php');
    }

    /**
     * Flushes entries tagged by the specified tag of all registered
     * caches.
     *
     * @param string $tag Tag to search for
     * @param boolean $flushPersistentCaches If set to TRUE, even those caches which are flagged as "persistent" will be flushed
     * @return void
     * @api
     */
    public function flushCachesByTag($tag, $flushPersistentCaches = false)
    {
        $this->createAllCaches();
        /** @var FrontendInterface $cache */
        foreach ($this->caches as $identifier => $cache) {
            if (!$flushPersistentCaches && $this->isCachePersistent($identifier)) {
                continue;
            }
            $cache->flushByTag($tag);
        }
    }

    /**
     * Returns an array of cache configurations, indexed by cache identifier
     *
     * @return array
     */
    public function getCacheConfigurations()
    {
        return $this->cacheConfigurations;
    }

    /**
     * Flushes entries tagged with class names if their class source files have changed.
     * Also flushes AOP proxy caches if a policy was modified.
     *
     * This method is used as a slot for a signal sent by the system file monitor
     * defined in the bootstrap scripts.
     *
     * Note: Policy configuration handling is implemented here as well as other parts
     *       of Flow (like the security framework) are not fully initialized at the
     *       time needed.
     *
     * @param string $fileMonitorIdentifier Identifier of the File Monitor
     * @param array $changedFiles A list of full paths to changed files
     * @return void
     */
    public function flushSystemCachesByChangedFiles($fileMonitorIdentifier, array $changedFiles)
    {
        switch ($fileMonitorIdentifier) {
            case 'Flow_ClassFiles':
                $this->flushClassCachesByChangedFiles($changedFiles);
                break;
            case 'Flow_ConfigurationFiles':
                $this->flushConfigurationCachesByChangedFiles($changedFiles);
                break;
            case 'Flow_TranslationFiles':
                $this->flushTranslationCachesByChangedFiles($changedFiles);
                break;
        }
    }

    /**
     * Flushes entries tagged with class names if their class source files have changed.
     *
     * @param array $changedFiles A list of full paths to changed files
     * @return void
     * @see flushSystemCachesByChangedFiles()
     */
    protected function flushClassCachesByChangedFiles(array $changedFiles)
    {
        $objectClassesCache = $this->getCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->getCache('Flow_Object_Configuration');
        $modifiedAspectClassNamesWithUnderscores = [];
        $modifiedClassNamesWithUnderscores = [];
        foreach ($changedFiles as $pathAndFilename => $status) {
            if (!file_exists($pathAndFilename)) {
                continue;
            }
            $fileContents = file_get_contents($pathAndFilename);
            $className = (new PhpAnalyzer($fileContents))->extractFullyQualifiedClassName();
            if ($className === null) {
                continue;
            }
            $classNameWithUnderscores = str_replace('\\', '_', $className);
            $modifiedClassNamesWithUnderscores[$classNameWithUnderscores] = true;

            // If an aspect was modified, the whole code cache needs to be flushed, so keep track of them:
            if (substr($classNameWithUnderscores, -6, 6) === 'Aspect') {
                $modifiedAspectClassNamesWithUnderscores[$classNameWithUnderscores] = true;
            }
            // As long as no modified aspect was found, we are optimistic that only part of the cache needs to be flushed:
            if (count($modifiedAspectClassNamesWithUnderscores) === 0) {
                $objectClassesCache->remove($classNameWithUnderscores);
            }
        }
        $flushDoctrineProxyCache = false;
        $flushPolicyCache = false;
        if (count($modifiedClassNamesWithUnderscores) > 0) {
            $reflectionStatusCache = $this->getCache('Flow_Reflection_Status');
            foreach (array_keys($modifiedClassNamesWithUnderscores) as $classNameWithUnderscores) {
                $reflectionStatusCache->remove($classNameWithUnderscores);
                if ($flushDoctrineProxyCache === false && preg_match('/_Domain_Model_(.+)/', $classNameWithUnderscores) === 1) {
                    $flushDoctrineProxyCache = true;
                }
                if ($flushPolicyCache === false && preg_match('/_Controller_(.+)Controller/', $classNameWithUnderscores) === 1) {
                    $flushPolicyCache = true;
                }
            }
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
        }
        if (count($modifiedAspectClassNamesWithUnderscores) > 0) {
            $this->systemLogger->log('Aspect classes have been modified, flushing the whole proxy classes cache.', LOG_INFO);
            $objectClassesCache->flush();
        }
        if ($flushDoctrineProxyCache === true) {
            $this->systemLogger->log('Domain model changes have been detected, triggering Doctrine 2 proxy rebuilding.', LOG_INFO);
            $this->getCache('Flow_Persistence_Doctrine')->flush();
            $objectConfigurationCache->remove('doctrineProxyCodeUpToDate');
        }
        if ($flushPolicyCache === true) {
            $this->systemLogger->log('Controller changes have been detected, trigger AOP rebuild.', LOG_INFO);
            $this->getCache('Flow_Security_Authorization_Privilege_Method')->flush();
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
        }
    }

    /**
     * Flushes caches as needed if settings, routes or policies have changed
     *
     * @param array $changedFiles A list of full paths to changed files
     * @return void
     * @see flushSystemCachesByChangedFiles()
     */
    protected function flushConfigurationCachesByChangedFiles(array $changedFiles)
    {
        $aopProxyClassRebuildIsNeeded = false;
        $aopProxyClassInfluencers = '/(?:Policy|Objects|Settings)(?:\..*)*\.yaml/';

        $objectClassesCache = $this->getCache('Flow_Object_Classes');
        $objectConfigurationCache = $this->getCache('Flow_Object_Configuration');
        $caches = [
            '/Policy\.yaml/' => ['Flow_Security_Authorization_Privilege_Method', 'Flow_Persistence_Doctrine', 'Flow_Persistence_Doctrine_Results', 'Flow_Aop_RuntimeExpressions'],
            '/Routes([^\/]*)\.yaml/' => ['Flow_Mvc_Routing_Route', 'Flow_Mvc_Routing_Resolve'],
            '/Views\.yaml/' => ['Flow_Mvc_ViewConfigurations']
        ];
        $cachesToFlush = [];
        foreach (array_keys($changedFiles) as $pathAndFilename) {
            foreach ($caches as $cacheFilePattern => $cacheNames) {
                if (preg_match($aopProxyClassInfluencers, $pathAndFilename) === 1) {
                    $aopProxyClassRebuildIsNeeded = true;
                }
                if (preg_match($cacheFilePattern, $pathAndFilename) !== 1) {
                    continue;
                }
                foreach ($caches[$cacheFilePattern] as $cacheName) {
                    $cachesToFlush[$cacheName] = $cacheFilePattern;
                }
            }
        }

        foreach ($cachesToFlush as $cacheName => $cacheFilePattern) {
            $this->systemLogger->log(sprintf('A configuration file matching the pattern "%s" has been changed, flushing related cache "%s"', $cacheFilePattern, $cacheName), LOG_INFO);
            $this->getCache($cacheName)->flush();
        }

        $this->systemLogger->log('A configuration file has been changed, refreshing compiled configuration cache', LOG_INFO);
        $this->configurationManager->refreshConfiguration();

        if ($aopProxyClassRebuildIsNeeded) {
            $this->systemLogger->log('The configuration has changed, triggering an AOP proxy class rebuild.', LOG_INFO);
            $objectConfigurationCache->remove('allAspectClassesUpToDate');
            $objectConfigurationCache->remove('allCompiledCodeUpToDate');
            $objectClassesCache->flush();
        }
    }

    /**
     * Flushes I18n caches if translation files have changed
     *
     * @param array $changedFiles A list of full paths to changed files
     * @return void
     * @see flushSystemCachesByChangedFiles()
     */
    protected function flushTranslationCachesByChangedFiles(array $changedFiles)
    {
        foreach ($changedFiles as $pathAndFilename => $status) {
            if (preg_match('/\/Translations\/.+\.xlf/', $pathAndFilename) === 1) {
                $this->systemLogger->log('The localization files have changed, thus flushing the I18n XML model cache.', LOG_INFO);
                $this->getCache('Flow_I18n_XmlModelCache')->flush();
                break;
            }
        }
    }

    /**
     * Instantiates all registered caches.
     *
     * @return void
     */
    protected function createAllCaches()
    {
        foreach (array_keys($this->cacheConfigurations) as $identifier) {
            if ($identifier !== 'Default' && !isset($this->caches[$identifier])) {
                $this->createCache($identifier);
            }
        }
    }

    /**
     * Instantiates the cache for $identifier.
     *
     * @param string $identifier
     * @return void
     */
    protected function createCache($identifier)
    {
        $frontend = isset($this->cacheConfigurations[$identifier]['frontend']) ? $this->cacheConfigurations[$identifier]['frontend'] : $this->cacheConfigurations['Default']['frontend'];
        $backend = isset($this->cacheConfigurations[$identifier]['backend']) ? $this->cacheConfigurations[$identifier]['backend'] : $this->cacheConfigurations['Default']['backend'];
        $backendOptions = isset($this->cacheConfigurations[$identifier]['backendOptions']) ? $this->cacheConfigurations[$identifier]['backendOptions'] : $this->cacheConfigurations['Default']['backendOptions'];
        $persistent = isset($this->cacheConfigurations[$identifier]['persistent']) ? $this->cacheConfigurations[$identifier]['persistent'] : $this->cacheConfigurations['Default']['persistent'];
        $cache = $this->cacheFactory->create($identifier, $frontend, $backend, $backendOptions, $persistent);
        $this->registerCache($cache, $persistent);
    }
}
