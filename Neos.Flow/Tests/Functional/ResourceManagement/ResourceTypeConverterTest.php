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

use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Utils;
use Neos\Error\Messages\Error;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\ResourceManagement\Exception;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceTypeConverter;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Files;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Functional tests for the ResourceTypeConverter
 */
class ResourceTypeConverterTest extends FunctionalTestCase
{
    /**
     * @var ResourceTypeConverter
     */
    protected $resourceTypeConverter;

    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    protected function setUp(): void
    {
        parent::setUp();
        if (!$this->persistenceManager instanceof PersistenceManager) {
            $this->markTestSkipped('Doctrine persistence is not enabled');
        }
        $this->resourceTypeConverter = $this->objectManager->get(ResourceTypeConverter::class);
    }

    public static function blockedFilenames(): array
    {
        return [
            'blocked extension' => ['blocked.phar'],
            'trailing dot' => ['blocked.php.'],
            'dot file' => ['.htaccess'],
            'blocked extension before another extension' => ['blocked.php.png'],
        ];
    }

    #[Test]
    #[DataProvider('blockedFilenames')]
    public function uploadedFilesWithBlockedExtensionsAreRejectedBeforeStoringTheContent(string $filename)
    {
        $content = 'uploaded file ' . $filename;
        $uploadedFile = new UploadedFile(Utils::streamFor($content), strlen($content), \UPLOAD_ERR_OK, $filename);

        $result = $this->resourceTypeConverter->convertFrom($uploadedFile, PersistentResource::class);

        self::assertInstanceOf(Error::class, $result);
        self::assertFileDoesNotExist($this->storagePathAndFilename($content));
    }

    #[Test]
    public function uploadedFilesWithAllowedExtensionsAreImported()
    {
        $content = 'uploaded file allowed.txt';
        $uploadedFile = new UploadedFile(Utils::streamFor($content), strlen($content), \UPLOAD_ERR_OK, 'allowed.txt');

        $result = $this->resourceTypeConverter->convertFrom($uploadedFile, PersistentResource::class);

        self::assertInstanceOf(PersistentResource::class, $result);
        self::assertSame('allowed.txt', $result->getFilename());
        self::assertStringEqualsFile($this->storagePathAndFilename($content), $content);
    }

    #[Test]
    #[DataProvider('blockedFilenames')]
    public function hashWithBlockedFilenameIsRejectedBeforeStoringTheFile(string $filename)
    {
        $content = 'load path ' . $filename;
        $loadPath = FLOW_PATH_TEMPORARY . 'ResourceTypeConverterTest';
        Files::createDirectoryRecursively($loadPath);
        file_put_contents($loadPath . '/' . sha1($content), $content);
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOption(ResourceTypeConverter::class, ResourceTypeConverter::CONFIGURATION_RESOURCE_LOAD_PATH, $loadPath);

        try {
            $this->resourceTypeConverter->convertFrom(['hash' => sha1($content), 'filename' => $filename], PersistentResource::class, [], $configuration);
            self::fail('Expected an exception for "' . $filename . '"');
        } catch (Exception $exception) {
            self::assertSame(1447148472, $exception->getCode());
        } finally {
            unlink($loadPath . '/' . sha1($content));
        }
        self::assertFileDoesNotExist($this->storagePathAndFilename($content));
    }

    private function storagePathAndFilename(string $content): string
    {
        $sha1 = sha1($content);
        return FLOW_PATH_DATA . 'Persistent/Test/Resources/' . $sha1[0] . '/' . $sha1[1] . '/' . $sha1[2] . '/' . $sha1[3] . '/' . $sha1;
    }
}
