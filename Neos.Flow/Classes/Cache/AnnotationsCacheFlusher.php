<?php
declare(strict_types=1);

namespace Neos\Flow\Cache;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

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
     * A slot that flushes caches as needed if classes with specific annotations have changed
     *
     * @param array<string> $classNames The full class names of the classes that got compiled
     * @return void
     */
    public function flushConfigurationCachesByCompiledClass(array $classNames): void
    {
        $caches = [
            Neos\Flow\Annotations\Route::class => ['Flow_Mvc_Routing_Route', 'Flow_Mvc_Routing_Resolve']
        ];
        $cachesToFlush = [];

        foreach ($classNames as $className) {
            foreach ($caches as $annotationClass => $cacheNames) {
                if (!$this->reflectionService->isClassAnnotatedWith($className, $annotationClass)
                    && count($this->reflectionService->getMethodsAnnotatedWith($className, $annotationClass)) === 0) {
                    continue;
                }
                foreach ($caches[$annotationClass] as $cacheName) {
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
