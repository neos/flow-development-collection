<?php
namespace TYPO3\Flow\Tests\Functional\Utility;

/*
 * This file is part of the Neos.Utility.Schema package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Utility\SchemaValidator;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Utility\Files;

/**
 * Testcase for the Flow Validation Framework
 *
 */
class SchemaSchemaTest extends FunctionalTestCase
{

    /**
     * @var array<string>
     */
    protected $schemaPackageKeys = ['TYPO3.Flow', 'TYPO3.Fluid', 'TYPO3.Eel', 'TYPO3.Kickstart'];

    /**
     * The schema-schema yaml
     *
     * @var string
     */
    protected $schemaSchemaResource = 'resource://Neos.Utility.Schema/Private/Schema/Schema.schema.yaml';

    /**
     * The parsed schema-schema
     *
     * @var array
     */
    protected $schemaSchema;

    /**
     * @var SchemaValidator
     */
    protected $schemaValidator;

    public function setUp()
    {
        parent::setUp();
        $this->schemaValidator = new SchemaValidator();
        $this->schemaSchema = \Symfony\Component\Yaml\Yaml::parse($this->schemaSchemaResource);
    }

    /**
     * @return array
     */
    public function schemaFilesAreValidDataProvider() {

        $bootstrap = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Core\Bootstrap::class);
        $objectManager = $bootstrap->getObjectManager();
        $packageManager = $objectManager->get(PackageManagerInterface::class);

        $activePackages = $packageManager->getActivePackages();
        foreach ($activePackages as $package) {
            $packageKey = $package->getPackageKey();
            if (in_array($packageKey, $this->schemaPackageKeys)) {
                $schemaPackages[] = $package;
            }
        }

        $schemaFiles = [];

        foreach ($schemaPackages as $package) {
            $packageSchemaPath = Files::concatenatePaths(array($package->getResourcesPath(), 'Private/Schema'));
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
     *
     * @test
     * @dataProvider schemaFilesAreValidDataProvider
     */
    public function schemaFilesAreValid($schemaFile)
    {
        $schema = \Symfony\Component\Yaml\Yaml::parse($schemaFile);
        $result = $this->schemaValidator->validate($schema, $this->schemaSchema);
        $hasErrors = $result->hasErrors();
        $message = sprintf('Schema-file "%s" is valid', $schemaFile);
        $this->assertFalse($hasErrors, $message);
    }
}
