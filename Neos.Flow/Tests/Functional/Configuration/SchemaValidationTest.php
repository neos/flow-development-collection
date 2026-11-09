<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Configuration;

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
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\Files;
use Neos\Utility\SchemaValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;

/**
 * Testcase for the Flow Validation Framework
 *
 * Not final: Neos.Neos extends this test case to validate its own schemas.
 */
class SchemaValidationTest extends FunctionalTestCase
{
    protected static array $schemaPackageKeys = ['Neos.Flow', 'Neos.FluidAdaptor', 'Neos.Eel', 'Neos.Kickstart'];

    /**
     * The schema-schema yaml
     */
    protected string $schemaSchemaResource = 'resource://Neos.Flow/Private/Schema/Schema.schema.yaml';

    /**
     * The parsed schema-schema
     */
    protected array $schemaSchema;

    protected SchemaValidator $schemaValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemaValidator = new SchemaValidator();
        $this->schemaSchema = Yaml::parseFile($this->schemaSchemaResource);
    }

    public static function schemaFilesAreValidDataProvider(): array
    {
        $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
        $objectManager = $bootstrap->getObjectManager();
        $packageManager = $objectManager->get(PackageManager::class);

        $activePackages = $packageManager->getAvailablePackages();
        $schemaPackages = [];
        foreach ($activePackages as $package) {
            $packageKey = $package->getPackageKey();
            if (in_array($packageKey, static::$schemaPackageKeys, true)) {
                $schemaPackages[] = $package;
            }
        }

        $schemaFiles = [];

        foreach ($schemaPackages as $package) {
            $packageSchemaPath = Files::concatenatePaths([$package->getResourcesPath(), 'Private/Schema']);
            if (is_dir($packageSchemaPath)) {
                foreach (Files::getRecursiveDirectoryGenerator($packageSchemaPath, '.schema.yaml') as $schemaFile) {
                    $schemaFiles[] = [$schemaFile];
                }
            }
        }
        return $schemaFiles;
    }

    /**
     * Validate that all the given files are valid schemas
     */
    #[DataProvider('schemaFilesAreValidDataProvider')]
    #[Test]
    public function schemaFilesAreValid(string $schemaFile): void
    {
        $schema = Yaml::parseFile($schemaFile);
        $result = $this->schemaValidator->validate($schema, $this->schemaSchema);
        $hasErrors = $result->hasErrors();

        $message = sprintf('Schema-file "%s" is not valid: %s', $schemaFile, $result->getFirstError());
        self::assertFalse($hasErrors, $message);
    }
}
