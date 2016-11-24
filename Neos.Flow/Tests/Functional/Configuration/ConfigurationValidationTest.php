<?php
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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\ConfigurationSchemaValidator;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Core\ApplicationContext;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for Configuration Validation
 */
class ConfigurationValidationTest extends FunctionalTestCase
{

    /**
     * @var array<string>
     */
    protected $contextNames = ['Development', 'Production', 'Testing'];

    /**
     * @var array<string>
     */
    protected $configurationTypes = ['Caches', 'Objects', 'Policy', 'Routes', 'Settings'];

    /**
     * @var array<string>
     */
    protected $schemaPackageKeys = ['Neos.Flow'];

    /**
     * @var array<string>
     */
    protected $configurationPackageKeys = ['Neos.Flow', 'Neos.FluidAdaptor', 'Neos.Eel', 'Neos.Kickstart'];

    /**
     *
     * @var ConfigurationSchemaValidator
     */
    protected $configurationSchemaValidator;

    /**
     * @var ConfigurationManager
     */
    protected $originalConfigurationManager;

    /**
     * @var ConfigurationManager
     */
    protected $mockConfigurationManager;

    /**
     * @var PackageManager
     */
    protected $mockPackageManager;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        //
        // create a mock packageManager that only returns the the packages that contain schema files
        //

        $schemaPackages = [];
        $configurationPackages = [];

        // get all packages and select the ones we want to test
        $temporaryPackageManager = $this->objectManager->get(PackageManagerInterface::class);
        foreach ($temporaryPackageManager->getActivePackages() as $package) {
            if (in_array($package->getPackageKey(), $this->getSchemaPackageKeys())) {
                $schemaPackages[$package->getPackageKey()] = $package;
            }
            if (in_array($package->getPackageKey(), $this->getConfigurationPackageKeys())) {
                $configurationPackages[$package->getPackageKey()] = $package;
            }
        }

        $this->mockPackageManager = $this->createMock(PackageManager::class);
        $this->mockPackageManager->expects($this->any())->method('getActivePackages')->will($this->returnValue($schemaPackages));

        //
        // create mock configurationManager and store the original one
        //

        $this->originalConfigurationManager = $this->objectManager->get(ConfigurationManager::class);

        $yamlConfigurationSource = $this->objectManager->get(\Neos\Flow\Tests\Functional\Configuration\Fixtures\RootDirectoryIgnoringYamlSource::class);

        $this->mockConfigurationManager = clone ($this->originalConfigurationManager);
        $this->mockConfigurationManager->setPackages($configurationPackages);
        $this->inject($this->mockConfigurationManager, 'configurationSource', $yamlConfigurationSource);

        $this->objectManager->setInstance(ConfigurationManager::class, $this->mockConfigurationManager);

        //
        // create the configurationSchemaValidator
        //

        $this->configurationSchemaValidator = $this->objectManager->get(ConfigurationSchemaValidator::class);
        $this->inject($this->configurationSchemaValidator, 'configurationManager', $this->mockConfigurationManager);
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->objectManager->setInstance(ConfigurationManager::class, $this->originalConfigurationManager);
        $this->injectApplicationContextIntoConfigurationManager($this->objectManager->getContext());
        parent::tearDown();
    }

    /**
     * @param ApplicationContext $context
     * @return void
     */
    protected function injectApplicationContextIntoConfigurationManager(ApplicationContext $context)
    {
        ObjectAccess::setProperty($this->mockConfigurationManager, 'configurations',
            [ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => []], true);
        ObjectAccess::setProperty($this->mockConfigurationManager, 'context', $context, true);
        ObjectAccess::setProperty($this->mockConfigurationManager, 'orderedListOfContextNames', [(string)$context],
            true);
        ObjectAccess::setProperty($this->mockConfigurationManager, 'includeCachedConfigurationsPathAndFilename',
            FLOW_PATH_CONFIGURATION . (string)$context . '/IncludeCachedConfigurations.php', true);
    }

    /**
     * @return array
     */
    public function configurationValidationDataProvider()
    {
        $result = [];
        foreach ($this->getContextNames() as $contextName) {
            foreach ($this->getConfigurationTypes() as $configurationType) {
                $result[] = ['contextName' => $contextName, 'configurationType' => $configurationType];
            }
        }
        return $result;
    }

    /**
     * @param string $contextName
     * @param string $configurationType
     * @test
     * @dataProvider configurationValidationDataProvider
     */
    public function configurationValidationTests($contextName, $configurationType)
    {
        $this->injectApplicationContextIntoConfigurationManager(new ApplicationContext($contextName));
        $schemaFiles = [];
        $validationResult = $this->configurationSchemaValidator->validate($configurationType, null, $schemaFiles);
        $this->assertValidationResultContainsNoErrors($validationResult);
    }

    /**
     * @param Result $validationResult
     * @return void
     */
    protected function assertValidationResultContainsNoErrors(Result $validationResult)
    {
        if ($validationResult->hasErrors()) {
            $errors = $validationResult->getFlattenedErrors();
            /** @var Error $error */
            $output = '';
            foreach ($errors as $path => $pathErrors) {
                foreach ($pathErrors as $error) {
                    $output .= sprintf('%s -> %s' . PHP_EOL, $path, $error->render());
                }
            }
            $this->fail($output);
        }
        $this->assertFalse($validationResult->hasErrors());
    }

    /**
     * @return array<string>
     */
    protected function getContextNames()
    {
        return $this->contextNames;
    }

    /**
     * @return array<string>
     */
    protected function getConfigurationTypes()
    {
        return $this->configurationTypes;
    }

    /**
     * @return array<string>
     */
    protected function getSchemaPackageKeys()
    {
        return $this->schemaPackageKeys;
    }

    /**
     * @return array<string>
     */
    protected function getConfigurationPackageKeys()
    {
        return $this->configurationPackageKeys;
    }
}
