<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\Flow\Cache;

use Neos\Cache\Exception\NoSuchCacheException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

/**
 * @Flow\Scope("singleton")
 */
final class AnnotationsCacheFlusher
{
    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * The ReflectionService is only needed during compile time for handling flushConfigurationCachesByCompiledClass
     *
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * Caches to flush for a given annotation
     *
     * @var array in the format [<AnnotationClassName> => [<CacheName_1>, <CacheName_2>]]
     */
    private $annotationToCachesMap = [];

    /**
     * Register an annotation that should trigger a cache flush
     *
     * @param string $annotationClassName fully qualified class name of the annotation
     * @param string[] $cacheNames Cache names to flush if a class containing the given annotation is compiled (e.g. ["Flow_Mvc_Routing_Route", Flow_Mvc_Routing_Resolve"])
     */
    public function registerAnnotation(string $annotationClassName, array $cacheNames): void
    {
        $this->annotationToCachesMap[$annotationClassName] = $cacheNames;
    }

    /**
     * A slot that flushes caches as needed if classes with specific annotations have changed @see registerAnnotation()
     *
     * @param array<string> $classNames The full class names of the classes that got compiled
     * @return void
     * @throws NoSuchCacheException
     */
    public function flushConfigurationCachesByCompiledClass(array $classNames): void
    {
        if ($this->annotationToCachesMap === []) {
            return;
        }
        $cachesToFlush = [];
        foreach ($classNames as $className) {
            foreach ($this->annotationToCachesMap as $annotationClass => $cacheNames) {
                if (!$this->reflectionService->isClassAnnotatedWith($className, $annotationClass)
                    && count($this->reflectionService->getMethodsAnnotatedWith($className, $annotationClass)) === 0) {
                    continue;
                }
                foreach ($cacheNames as $cacheName) {
                    $cachesToFlush[$cacheName] = $annotationClass;
                }
            }
        }

        foreach ($cachesToFlush as $cacheName => $annotationClass) {
            $this->logger->info(sprintf('A class file containing the annotation "%s" has been changed, flushing related cache "%s"', $annotationClass, $cacheName), LogEnvironment::fromMethodName(__METHOD__));
            $this->cacheManager->getCache($cacheName)->flush();
        }

        if (count($cachesToFlush) > 0) {
            $this->logger->info('An annotated class file has been changed, refreshing compiled configuration cache', LogEnvironment::fromMethodName(__METHOD__));
            $this->configurationManager->refreshConfiguration();
        }
    }
}
