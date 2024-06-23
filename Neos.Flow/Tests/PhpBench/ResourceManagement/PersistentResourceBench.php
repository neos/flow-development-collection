<?php

/*
 * (c) Contributors of the Neos Project - www.neos.io
 * Please see the LICENSE file which was distributed with this source code.
 */

declare(strict_types=1);

namespace Neos\Flow\Tests\PhpBench\ResourceManagement;

use Neos\BuildEssentials\PhpBench\FrameworkEnabledBenchmark;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceRepository;

/**
 * Benchmark cases for PersistentResources
 * Checks performance of creating a PersistentResource (object) as
 * well as persisting it via ORM
 *
 * @BeforeClassMethods("enablePersistence")
 * @AfterClassMethods("cleanUpPersistence")
 */
class PersistentResourceBench extends FrameworkEnabledBenchmark
{
    public static function enablePersistence(): void
    {
        parent::enablePersistence();
    }

    /**
     * This should be a good measure of our UUID persistence magic aspect
     *
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(10)
     */
    public function benchCreatePersistentResourceObjectWithoutPersist(): void
    {
        $persistentResource = new PersistentResource();
    }

    /**
     * We should actually benchmark things like this in a benchmark for the ReflectionService
     *
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(10)
     */
    public function benchRequestPersistentResourceSchema(): void
    {
        /** @var ReflectionService $reflectionService */
        $reflectionService = $this->flowBootstrap->getObjectManager()->get(ReflectionService::class);
        $reflectionService->getClassSchema(PersistentResource::class);
    }

    /**
     * Create and persist a resource object, mostly a test for ORM enetity persistence speed
     *
     * @BeforeMethods("bootstrapWithTestRequestHandler")
     * @Revs(3)
     */
    public function benchCreatePersistentResourceObjectAndPersist(): void
    {
        $resourceRepository = $this->flowBootstrap->getObjectManager()->get(ResourceRepository::class);
        $persistentResource = new PersistentResource();
        $persistentResource->disableLifecycleEvents();
        $persistentResource->setFilename('benchCreatePersistentResourceObjectAndPersist.empty');
        $persistentResource->setFileSize(0);
        $persistentResource->setCollectionName('default');
        $persistentResource->setMediaType('text/plain');
        $persistentResource->setSha1('da39a3ee5e6b4b0d3255bfef95601890afd80709');
        $resourceRepository->add($persistentResource);
        $persistenceManager = $this->flowBootstrap->getObjectManager()->get(PersistenceManagerInterface::class);
        $persistenceManager->persistAll();
    }
}
