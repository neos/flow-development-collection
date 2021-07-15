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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\ConfigurationSchemaValidator;
use Neos\Flow\Configuration\Loader\RoutesLoader;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Core\ApplicationContext;
use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Flow\Tests\Functional\Configuration\Fixtures\RootDirectoryIgnoringYamlSource;
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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        //
        // create a mock packageManager that only returns the the packages that contain schema files
        //

        $schemaPackages = [];
        $configurationPackages = [];

        // get all packages and select the ones we want to test
        $temporaryPackageManager = $this->objectManager->get(PackageManager::class);
        foreach ($temporaryPackageManager->getAvailablePackages() as $package) {
            if (in_array($package->getPackageKey(), $this->getSchemaPackageKeys())) {
                $schemaPackages[$package->getPackageKey()] = $package;
            }
            if (in_array($package->getPackageKey(), $this->getConfigurationPackageKeys())) {
                $configurationPackages[$package->getPackageKey()] = $package;
            }
        }

        //
        // create mock configurationManager and store the original one
        //
        $this->originalConfigurationManager = $this->objectManager->get(ConfigurationManager::class);

        $rootDirectoryIgnoringYamlSource = $this->objectManager->get(RootDirectoryIgnoringYamlSource::class);

        $this->mockConfigurationManager = clone $this->originalConfigurationManager;
        $this->mockConfigurationManager->setPackages($configurationPackages);
        $this->mockConfigurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, new SettingsLoader($rootDirectoryIgnoringYamlSource));
        $this->mockConfigurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, new RoutesLoader($rootDirectoryIgnoringYamlSource, $this->mockConfigurationManager));

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
    protected function tearDown(): void
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
        ObjectAccess::setProperty(
            $this->mockConfigurationManager,
            'configurations',
            [ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => []],
            true
        );
        ObjectAccess::setProperty($this->mockConfigurationManager, 'context', $context, true);
        ObjectAccess::setProperty(
            $this->mockConfigurationManager,
            'includeCachedConfigurationsPathAndFilename',
            FLOW_PATH_CONFIGURATION . $context . '/IncludeCachedConfigurations.php',
            true
        );
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
        self::assertFalse($validationResult->hasErrors());
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
