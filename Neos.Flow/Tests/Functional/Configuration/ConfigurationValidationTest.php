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

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\ConfigurationSchemaValidator;
use Neos\Flow\Configuration\Loader\RoutesLoader;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\Functional\Configuration\Fixtures\RootDirectoryIgnoringYamlSource;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\ObjectAccess;

/**
 * Testcase for Configuration Validation
 */
class ConfigurationValidationTest extends FunctionalTestCase
{
    protected array $contextNames = ['Development', 'Production', 'Testing'];
    protected array $configurationTypes = ['Caches', 'Objects', 'Policy', 'Routes', 'Settings'];
    protected array $schemaPackageKeys = ['Neos.Flow'];
    protected array $configurationPackageKeys = ['Neos.Flow', 'Neos.FluidAdaptor', 'Neos.Eel', 'Neos.Kickstart'];
    protected ConfigurationSchemaValidator $configurationSchemaValidator;
    protected ConfigurationManager $originalConfigurationManager;
    protected ConfigurationManager $mockConfigurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        //
        // create a mock packageManager that only returns the packages that contain schema files
        //

        $configurationPackages = [];

        // get all packages and select the ones we want to test
        $temporaryPackageManager = $this->objectManager->get(PackageManager::class);
        foreach ($temporaryPackageManager->getAvailablePackages() as $package) {
            if (in_array($package->getPackageKey(), $this->getConfigurationPackageKeys(), true)) {
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

    protected function tearDown(): void
    {
        $this->objectManager->setInstance(ConfigurationManager::class, $this->originalConfigurationManager);
        $this->injectApplicationContextIntoConfigurationManager($this->objectManager->getContext());
        parent::tearDown();
    }

    protected function injectApplicationContextIntoConfigurationManager(ApplicationContext $context): void
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

    public function configurationValidationDataProvider(): array
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
     * @test
     * @dataProvider configurationValidationDataProvider
     * @throws
     */
    public function configurationValidationTests(string $contextName, string $configurationType): void
    {
        $this->injectApplicationContextIntoConfigurationManager(new ApplicationContext($contextName));
        $schemaFiles = [];
        $validationResult = $this->configurationSchemaValidator->validate($configurationType, null, $schemaFiles);
        $this->assertValidationResultContainsNoErrors($validationResult);
    }

    protected function assertValidationResultContainsNoErrors(Result $validationResult): void
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

    protected function getContextNames(): array
    {
        return $this->contextNames;
    }

    protected function getConfigurationTypes(): array
    {
        return $this->configurationTypes;
    }

    protected function getSchemaPackageKeys(): array
    {
        return $this->schemaPackageKeys;
    }

    protected function getConfigurationPackageKeys(): array
    {
        return $this->configurationPackageKeys;
    }
}
