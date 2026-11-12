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
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Files;

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

    public function blockedFilenames(): array
    {
        return [
            'blocked extension' => ['blocked.php'],
            'blocked extension in upper case' => ['blocked.PHAR'],
            'trailing dot' => ['blocked.php.'],
            'trailing whitespace' => ['blocked.php '],
            'dot file' => ['.htaccess'],
            'blocked extension before another extension' => ['blocked.php.txt'],
            'blocked extension in upper case before other extensions' => ['blocked.PHAR.tar.gz'],
            'blocked non-PHP extension before another extension' => ['blocked.pl.txt'],
        ];
    }

    /**
     * @test
     * @dataProvider blockedFilenames
     */
    public function importResourceFromContentRejectsBlockedExtensionsBeforeStoringTheContent(string $filename)
    {
        $content = 'importResourceFromContent ' . $filename;
        try {
            $this->resourceManager->importResourceFromContent($content, $filename);
            self::fail('Expected an exception for "' . $filename . '"');
        } catch (Exception $exception) {
            self::assertSame(1447148472, $exception->getCode());
        }
        self::assertFileDoesNotExist($this->storagePathAndFilename($content));
    }

    /**
     * @test
     * @dataProvider blockedFilenames
     */
    public function importResourceRejectsBlockedExtensionsBeforeStoringTheFile(string $filename)
    {
        $content = 'importResource ' . $filename;
        $temporaryDirectory = FLOW_PATH_TEMPORARY . 'ResourceManagerTest/';
        Files::createDirectoryRecursively($temporaryDirectory);
        file_put_contents($temporaryDirectory . $filename, $content);
        try {
            $this->resourceManager->importResource($temporaryDirectory . $filename);
            self::fail('Expected an exception for "' . $filename . '"');
        } catch (Exception $exception) {
            self::assertSame(1447148472, $exception->getCode());
        } finally {
            unlink($temporaryDirectory . $filename);
        }
        self::assertFileDoesNotExist($this->storagePathAndFilename($content));
    }

    /**
     * @test
     */
    public function importResourceFromContentStoresFilesWithSeveralAllowedExtensions()
    {
        $resource = $this->resourceManager->importResourceFromContent('allowed', 'allowed.tar.gz');

        self::assertSame('allowed.tar.gz', $resource->getFilename());
        self::assertStringEqualsFile($this->storagePathAndFilename('allowed'), 'allowed');
    }

    private function storagePathAndFilename(string $content): string
    {
        $sha1 = sha1($content);
        return FLOW_PATH_DATA . 'Persistent/Test/Resources/' . $sha1[0] . '/' . $sha1[1] . '/' . $sha1[2] . '/' . $sha1[3] . '/' . $sha1;
    }
}
