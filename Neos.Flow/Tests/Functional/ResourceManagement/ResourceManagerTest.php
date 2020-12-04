<?php
namespace Neos\Flow\Tests\Functional\ResourceManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the ResourceManager
 */
class ResourceManagerTest extends FunctionalTestCase
{
    /**
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->resourceManager = $this->objectManager->get(ResourceManager::class);
        $this->resourceRepository = $this->objectManager->get(ResourceRepository::class);
    }

    /**
     * @test
     */
    public function deleteResourceKeepsDataIfStillInUse()
    {
        $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt');
        $otherResource = $this->resourceManager->importResourceFromContent('fixture', 'other-fixture.txt');

        $this->resourceManager->deleteResource($otherResource);

        self::assertStringEqualsFile(FLOW_PATH_DATA . 'Persistent/Test/Resources/5/1/c/f/51cff3c1f0bc59f6187e7040cc12a4e9b1eca7aa', 'fixture');
    }

    /**
     * @test
     */
    public function deleteResourceRemovesDataIfStillInUseButCollectionDiffersWithoutPersistAll()
    {
        $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt');
        $otherResource = $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt', 'custom');

        $this->resourceManager->deleteResource($otherResource);

        self::assertStringEqualsFile(FLOW_PATH_DATA . 'Persistent/Test/Resources/5/1/c/f/51cff3c1f0bc59f6187e7040cc12a4e9b1eca7aa', 'fixture');
        self::assertFileDoesNotExist(FLOW_PATH_DATA . 'Persistent/Test/CustomResources/5/1/c/f/51cff3c1f0bc59f6187e7040cc12a4e9b1eca7aa');
    }

    /**
     * @test
     */
    public function deleteResourceRemovesDataIfStillInUseButCollectionDiffersWithPersistAll()
    {
        $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt');
        $otherResource = $this->resourceManager->importResourceFromContent('fixture', 'fixture.txt', 'custom');

        $this->persistenceManager->persistAll();
        $this->resourceManager->deleteResource($otherResource);

        self::assertStringEqualsFile(FLOW_PATH_DATA . 'Persistent/Test/Resources/5/1/c/f/51cff3c1f0bc59f6187e7040cc12a4e9b1eca7aa', 'fixture');
        self::assertFileDoesNotExist(FLOW_PATH_DATA . 'Persistent/Test/CustomResources/5/1/c/f/51cff3c1f0bc59f6187e7040cc12a4e9b1eca7aa');
    }
}
