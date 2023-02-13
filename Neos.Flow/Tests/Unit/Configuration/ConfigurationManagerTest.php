<?php
namespace Neos\Flow\Tests\Unit\Configuration;

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
use Neos\Flow\Configuration\Loader\AppendLoader;
use Neos\Flow\Configuration\Loader\LoaderInterface;
use Neos\Flow\Configuration\Loader\MergeLoader;
use Neos\Flow\Configuration\Loader\ObjectsLoader;
use Neos\Flow\Configuration\Loader\RoutesLoader;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Configuration\Exception\ParseErrorException;
use Neos\Flow\Configuration\Exception\RecursionException;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\Package;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the configuration manager
 */
class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ApplicationContext|MockObject
     */
    protected $mockContext;

    protected function setUp(): void
    {
        $this->mockContext = new ApplicationContext('Testing');
    }

    /**
     * @test
     */
    public function getConfigurationForSettingsLoadsConfigurationIfNecessary()
    {
        $initialConfigurations = [
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => [],
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration', 'processConfigurationType']);
        $configurationManager->_set('configurations', $initialConfigurations);

        $configurationManager->expects(self::once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
        $configurationManager->expects(self::once())->method('processConfigurationType')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo');
    }

    /**
     * @test
     */
    public function getConfigurationForTypeSettingsReturnsRespectiveConfigurationArray()
    {
        $expectedConfiguration = ['foo' => 'bar'];
        $configurations = [
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => [
                'SomePackage' => $expectedConfiguration
            ]
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $configurationManager->_set('configurations', $configurations);

        $actualConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
        self::assertSame($expectedConfiguration, $actualConfiguration);
    }

    /**
     * @test
     */
    public function getConfigurationForTypeSettingsLoadsConfigurationIfNecessary()
    {
        $packages = ['SomePackage' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock()];

        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration', 'processConfigurationType']);
        $configurationManager->_set('configurations', [ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => []]);
        $configurationManager->setPackages($packages);
        $configurationManager->expects(self::once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packages);
        $configurationManager->expects(self::once())->method('processConfigurationType')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);

        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
    }

    /**
     * @test
     */
    public function getConfigurationForTypeObjectLoadsConfiguration()
    {
        $packages = ['SomePackage' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock()];

        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration', 'processConfigurationType']);
        $configurationManager->_set('configurations', [ConfigurationManager::CONFIGURATION_TYPE_OBJECTS => []]);
        $configurationManager->setPackages($packages);
        $configurationManager->expects(self::once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $packages);
        $configurationManager->expects(self::once())->method('processConfigurationType')->with(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);

        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'SomePackage');
    }

    /**
     * @test
     */
    public function getConfigurationForRoutesAndCachesLoadsConfigurationIfNecessary()
    {
        $initialConfigurations = [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES => ['foo' => 'bar'],
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration', 'processConfigurationType']);
        $configurationManager->_set('configurations', $initialConfigurations);

        $configurationManager->expects(self::atLeastOnce())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_CACHES);
        $configurationManager->expects(self::atLeastOnce())->method('processConfigurationType')->with(ConfigurationManager::CONFIGURATION_TYPE_CACHES);

        $configurationTypes = [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
            ConfigurationManager::CONFIGURATION_TYPE_CACHES
        ];
        foreach ($configurationTypes as $configurationType) {
            $configurationManager->getConfiguration($configurationType);
        }
    }

    /**
     * @test
     */
    public function getConfigurationForRoutesAndCachesReturnsRespectiveConfigurationArray()
    {
        $expectedConfigurations = [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES => ['routes'],
            ConfigurationManager::CONFIGURATION_TYPE_CACHES => ['caches']
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration']);
        $configurationManager->_set('configurations', $expectedConfigurations);
        $configurationManager->expects(self::never())->method('loadConfiguration');

        foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
            $actualConfiguration = $configurationManager->getConfiguration($configurationType);
            self::assertSame($expectedConfiguration, $actualConfiguration);
        }
    }

    /**
     * @test
     */
    public function gettingUnregisteredConfigurationTypeFails()
    {
        $this->expectException(InvalidConfigurationTypeException::class);
        $configurationManager = new ConfigurationManager(new ApplicationContext('Testing'));
        $configurationManager->getConfiguration('Custom');
    }

    /**
     * @test
     */
    public function registerConfigurationTypeThrowsExceptionOnInvalidConfigurationProcessingType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $configurationManager = $this->getAccessibleConfigurationManager(['loadConfiguration']);
        $configurationManager->registerConfigurationType('MyCustomType', 'Nonsense');
    }

    /**
     * @test
     */
    public function loadConfigurationOverridesSettingsByContext()
    {
        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnCallBack([$this, 'packageSettingsCallback']));

        $mockPackageA = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackageA->expects(self::any())->method('getConfigurationPath')->will(self::returnValue('PackageA/Configuration/'));
        $mockPackageA->expects(self::any())->method('getPackageKey')->will(self::returnValue('PackageA'));

        $mockPackages = [
            'PackageA' => $mockPackageA,
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType']);
        $configurationManager->_set('configurationSource', $mockYamlSource);

        $settingsLoader = new SettingsLoader($mockYamlSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedSettings = [
            'foo' => 'D',
            'bar' => 'A'
        ];

        self::assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['PackageA']);
    }

    /**
     * @test
     */
    public function loadConfigurationOverridesGlobalSettingsByContext()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageSettingsCallback', 'Testing/System1');
        $mockPackages = $this->getMockPackages();

        $configurationSource = $configurationManager->_get('configurationSource');
        $settingsLoader = new SettingsLoader($configurationSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedSettings = [
            'Neos' => [
                'Flow' => [
                    'ex1' => 'global',
                    'foo' => 'quux',
                    'example' => 'fromTestingSystem1',
                    'core' => ['context' => 'Testing/System1'],
                ],
                'Testing' => [
                    'filters' => []
                ]
            ]
        ];

        self::assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
    }

    /**
     * Callback for the above test.
     *
     */
    public function packageSettingsCallback()
    {
        $filenameAndPath = func_get_arg(0);

        $settingsFlow = [
            'Neos' => [
                'Flow' => [
                    'ex1' => 'global',
                    'foo' => 'global stuff'
                ],
                'Testing' => [
                    'filters' => [
                        'foo' => 'bar'
                    ]
                ]
            ]
        ];

        $settingsFlowTesting = [
            'Neos' => [
                'Flow' => [
                    'foo' => 'quux',
                    'example' => 'fromTesting'
                ],
                'Testing' => [
                    'filters' => []
                ]
            ]
        ];

        $settingsFlowTestingSystem1 = [
            'Neos' => [
                'Flow' => [
                    'foo' => 'quux',
                    'example' => 'fromTestingSystem1'
                ]
            ]
        ];

        $settingsA = [
            'PackageA' => [
                'foo' => 'A',
                'bar' => 'A'
            ]
        ];

        $settingsB = [
            'PackageA' => [
                'bar' => 'B'
            ],
            'PackageB' => [
                'foo' => 'B',
                'bar' => 'B'
            ]
        ];

        $settingsC = [
            'PackageA' => [
                'bar' => 'C'
            ],
            'PackageC' => [
                'baz' => 'C'
            ]
        ];

        $settingsATesting = [
            'PackageA' => [
                'foo' => 'D'
            ]
        ];

        $globalSettings = [
            'Neos' => [
                'Flow' => [
                    'foo' => 'bar'
                ]
            ]
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Settings': return $settingsFlow;
            case 'Flow/Configuration/SomeContext/Settings': return [];
            case 'Flow/Configuration/Testing/Settings': return $settingsFlowTesting;
            case 'Flow/Configuration/Testing/System1/Settings': return $settingsFlowTestingSystem1;

            case 'PackageA/Configuration/Settings': return $settingsA;
            case 'PackageA/Configuration/SomeContext/Settings': return [];
            case 'PackageA/Configuration/Testing/Settings': return $settingsATesting;
            case 'PackageB/Configuration/Settings': return $settingsB;
            case 'PackageB/Configuration/SomeContext/Settings': return [];
            case 'PackageB/Configuration/Testing/Settings': return [];
            case 'PackageC/Configuration/Settings': return $settingsC;
            case 'PackageC/Configuration/SomeContext/Settings': return [];
            case 'PackageC/Configuration/Testing/Settings': return [];

            case FLOW_PATH_CONFIGURATION . 'Settings': return $globalSettings;
            case FLOW_PATH_CONFIGURATION . 'SomeContext/Settings': return [];
            case FLOW_PATH_CONFIGURATION . 'Testing/Settings': return [];
            case FLOW_PATH_CONFIGURATION . 'Testing/System1/Settings': return [];
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }

    /**
     * @test
     */
    public function loadConfigurationForObjectsOverridesConfigurationByContext()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageObjectsCallback', 'Testing/System1');
        $mockPackages = $this->getMockPackages();

        $configurationSource = $configurationManager->_get('configurationSource');
        $objectsLoader = new ObjectsLoader($configurationSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $objectsLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedSettings = [
            'Neos.Flow' => [
                SomeClass::class => [
                    'className' => 'Bar',
                    'configPackageObjects' => 'correct',
                    'configGlobalObjects' => 'correct',
                    'configPackageContextObjects' => 'correct',
                    'configGlobalContextObjects' => 'correct',
                    'configPackageSubContextObjects' => 'correct',
                    'configGlobalSubContextObjects' => 'correct',
                ]
            ]
        ];

        self::assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_OBJECTS]);
    }

    /**
     * Callback for the above test.
     */
    public function packageObjectsCallback()
    {
        $filenameAndPath = func_get_arg(0);

        // We expect the following overriding order:
        // - $packageObjects
        // - $globalObjects
        // - $packageContextObjects
        // - $globalContextObjects
        // - $packageSubContextObjects
        // - $globalSubContextObjects
        $packageObjects = [
            SomeClass::class => [
                'className' => 'Foo',
                'configPackageObjects' => 'correct',

                'configGlobalObjects' => 'overriddenWronglyFromPackageObjects',
                'configPackageContextObjects' => 'overriddenWronglyFromPackageObjects',
                'configGlobalContextObjects' => 'overriddenWronglyFromPackageObjects',
                'configPackageSubContextObjects' => 'overriddenWronglyFromPackageObjects',
                'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageObjects',
            ]
        ];

        $globalObjects = [
            SomeClass::class => [
                'configGlobalObjects' => 'correct',

                'configPackageContextObjects' => 'overriddenWronglyFromGlobalObjects',
                'configGlobalContextObjects' => 'overriddenWronglyFromGlobalObjects',
                'configPackageSubContextObjects' => 'overriddenWronglyFromGlobalObjects',
                'configGlobalSubContextObjects' => 'overriddenWronglyFromGlobalObjects',
            ]
        ];

        $packageContextObjects = [
            SomeClass::class => [
                'className' => 'Bar',

                'configPackageContextObjects' => 'correct',

                'configGlobalContextObjects' => 'overriddenWronglyFromPackageContextObjects',
                'configPackageSubContextObjects' => 'overriddenWronglyFromPackageContextObjects',
                'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageContextObjects',
            ]
        ];

        $globalContextObjects = [
            SomeClass::class => [
                'configGlobalContextObjects' => 'correct',

                'configPackageSubContextObjects' => 'overriddenWronglyFromGlobalContextObjects',
                'configGlobalSubContextObjects' => 'overriddenWronglyFromGlobalContextObjects',
            ]
        ];

        $packageSubContextObjects = [
            SomeClass::class => [
                'configPackageSubContextObjects' => 'correct',

                'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageSubContextObjects',
            ]
        ];

        $globalSubContextObjects = [
            SomeClass::class => [
                'configGlobalSubContextObjects' => 'correct',
            ]
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Objects': return $packageObjects;
            case 'Flow/Configuration/Testing/Objects': return $packageContextObjects;
            case 'Flow/Configuration/Testing/System1/Objects': return $packageSubContextObjects;
            case FLOW_PATH_CONFIGURATION . 'Objects': return $globalObjects;
            case FLOW_PATH_CONFIGURATION . 'Testing/Objects': return $globalContextObjects;
            case FLOW_PATH_CONFIGURATION . 'Testing/System1/Objects': return $globalSubContextObjects;
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }


    /**
     * @test
     */
    public function loadConfigurationForCachesOverridesConfigurationByContext()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageCachesCallback', 'Testing/System1');
        $mockPackages = $this->getMockPackages();

        $configurationSource = $configurationManager->_get('configurationSource');
        $mergeLoader = new MergeLoader($configurationSource, ConfigurationManager::CONFIGURATION_TYPE_CACHES);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_CACHES, $mergeLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_CACHES, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedCachesConfiguration = [
            \Neos_Flow_SomeCache::class => [
                'configPackageCaches' => 'correct',
                'configGlobalCaches' => 'correct',
                'configPackageContextCaches' => 'correct',
                'configGlobalContextCaches' => 'correct',
                'configPackageSubContextCaches' => 'correct',
                'configGlobalSubContextCaches' => 'correct',
            ]
        ];

        self::assertSame($expectedCachesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_CACHES]);
    }

    /**
     * Callback for the above test.
     */
    public function packageCachesCallback()
    {
        $filenameAndPath = func_get_arg(0);

        // We expect the following overriding order:
        // - $packageCaches
        // - $globalCaches
        // - $packageContextCaches
        // - $globalContextCaches
        // - $packageSubContextCaches
        // - $globalSubContextCaches
        $packageCaches = [
            \Neos_Flow_SomeCache::class => [
                'configPackageCaches' => 'correct',

                'configGlobalCaches' => 'overriddenWronglyFromPackageCaches',
                'configPackageContextCaches' => 'overriddenWronglyFromPackageCaches',
                'configGlobalContextCaches' => 'overriddenWronglyFromPackageCaches',
                'configPackageSubContextCaches' => 'overriddenWronglyFromPackageCaches',
                'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageCaches',
            ]
        ];

        $globalCaches = [
            \Neos_Flow_SomeCache::class => [
                'configGlobalCaches' => 'correct',

                'configPackageContextCaches' => 'overriddenWronglyFromGlobalCaches',
                'configGlobalContextCaches' => 'overriddenWronglyFromGlobalCaches',
                'configPackageSubContextCaches' => 'overriddenWronglyFromGlobalCaches',
                'configGlobalSubContextCaches' => 'overriddenWronglyFromGlobalCaches',
            ]
        ];

        $packageContextCaches = [
            \Neos_Flow_SomeCache::class => [
                'configPackageContextCaches' => 'correct',

                'configGlobalContextCaches' => 'overriddenWronglyFromPackageContextCaches',
                'configPackageSubContextCaches' => 'overriddenWronglyFromPackageContextCaches',
                'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageContextCaches',
            ]
        ];

        $globalContextCaches = [
            \Neos_Flow_SomeCache::class => [
                'configGlobalContextCaches' => 'correct',

                'configPackageSubContextCaches' => 'overriddenWronglyFromGlobalContextCaches',
                'configGlobalSubContextCaches' => 'overriddenWronglyFromGlobalContextCaches',
            ]
        ];

        $packageSubContextCaches = [
            \Neos_Flow_SomeCache::class => [
                'configPackageSubContextCaches' => 'correct',

                'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageSubContextCaches',
            ]
        ];

        $globalSubContextCaches = [
            \Neos_Flow_SomeCache::class => [
                'configGlobalSubContextCaches' => 'correct',
            ]
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Caches': return $packageCaches;
            case 'Flow/Configuration/Testing/Caches': return $packageContextCaches;
            case 'Flow/Configuration/Testing/System1/Caches': return $packageSubContextCaches;
            case FLOW_PATH_CONFIGURATION . 'Caches': return $globalCaches;
            case FLOW_PATH_CONFIGURATION . 'Testing/Caches': return $globalContextCaches;
            case FLOW_PATH_CONFIGURATION . 'Testing/System1/Caches': return $globalSubContextCaches;
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }

    /**
     * @test
     */
    public function loadConfigurationCacheLoadsConfigurationsFromCacheIfACacheFileExists()
    {
        vfsStream::setup('Flow/Cache');

        $configurationsCode = <<< "EOD"
<?php
return array('bar' => 'touched');
?>
EOD;

        $cachedConfigurationsPathAndFilename = vfsStream::url('Flow/Cache/Configurations.php');
        file_put_contents($cachedConfigurationsPathAndFilename, $configurationsCode);

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType', 'constructConfigurationCachePath', 'refreshConfiguration']);
        $configurationManager->expects(self::any())->method('constructConfigurationCachePath')->willReturn('notfound.php', $cachedConfigurationsPathAndFilename);
        $configurationManager->_set('configurations', ['foo' => 'untouched']);
        $configurationManager->_call('loadConfigurationsFromCache');
        self::assertSame(['foo' => 'untouched'], $configurationManager->_get('configurations'));

        $configurationManager->_call('loadConfigurationsFromCache');
        self::assertSame(['bar' => 'touched'], $configurationManager->_get('configurations'));
    }

    /**
     * @test
     */
    public function loadConfigurationCorrectlyMergesSettings()
    {
        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnCallBack([$this, 'packageSettingsCallback']));

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType']);
        $configurationManager->_set('configurationSource', $mockYamlSource);

        $settingsLoader = new SettingsLoader($mockYamlSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, []);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedConfiguration = [
            'Neos' => [
                'Flow' => [
                    'foo' => 'bar',
                    'core' => ['context' => 'Testing']
                ]
            ]
        ];
        self::assertEquals($expectedConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
    }

    /**
     * @test
     */
    public function saveConfigurationCacheSavesTheCurrentConfigurationAsPhpCode()
    {
        vfsStream::setup('Flow');
        mkdir(vfsStream::url('Flow/Cache'));

        $temporaryDirectoryPath = 'vfs://Flow/Cache/';
        $cachedConfigurationsPathAndFilename = vfsStream::url('Flow/Cache/Configurations.php');

        $mockConfigurations = [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES => ['routes'],
            ConfigurationManager::CONFIGURATION_TYPE_CACHES => ['caches'],
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => ['settings' => ['foo' => 'bar']]
        ];

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType', 'constructConfigurationCachePath', 'loadConfigurationCache']);
        $configurationManager->method('constructConfigurationCachePath')->willReturn($cachedConfigurationsPathAndFilename);
        $configurationManager->setTemporaryDirectoryPath($temporaryDirectoryPath);
        $configurationManager->_set('configurations', $mockConfigurations);
        $configurationManager->_set('unprocessedConfiguration', $mockConfigurations);
        $configurationManager->_set('configurationTypes', [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES => [
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_ROUTES,
                'allowSplitSource' => false
            ],
            ConfigurationManager::CONFIGURATION_TYPE_CACHES => [
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
                'allowSplitSource' => false
            ],
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => [
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
                'allowSplitSource' => false
            ],
        ]);

        $configurationManager->_call('saveConfigurationCache');

        $expectedInclusionCode = '<?php return ' . var_export($mockConfigurations, true) . ';';
        $this->assertStringEqualsFile($cachedConfigurationsPathAndFilename, $expectedInclusionCode);
    }

    /**
     * @test
     */
    public function replaceVariablesInPhpStringReplacesConstantMarkersByRealGlobalConstantCode()
    {
        $settings = [
            'foo' => 'bar',
            'baz' => '%PHP_VERSION%',
            'inspiring' => [
                'people' => [
                    'to' => '%FLOW_PATH_ROOT%'
                ]
            ]
        ];
        $settingsPhpString = var_export($settings, true);
        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        self::assertStringContainsString("'baz' => (defined('PHP_VERSION') ? constant('PHP_VERSION') : null)", $processedPhpString);
        self::assertStringContainsString("'to' => (defined('FLOW_PATH_ROOT') ? constant('FLOW_PATH_ROOT') : null)", $processedPhpString);
    }

    /**
     * @test
     */
    public function replaceVariablesInPhpStringMaintainsConstantTypeIfOnlyValue()
    {
        $settings = [
            'foo' => 'bar',
            'anIntegerConstant' => '%PHP_VERSION_ID%',
            'casted' => [
                'to' => [
                    'string' => 'Version id is %PHP_VERSION_ID%'
                ]
            ]
        ];
        $settingsPhpString = var_export($settings, true);

        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');
        $this->assertIsInt($settings['anIntegerConstant']);
        self::assertSame(PHP_VERSION_ID, $settings['anIntegerConstant']);

        $this->assertIsString($settings['casted']['to']['string']);
        self::assertSame('Version id is ' . PHP_VERSION_ID, $settings['casted']['to']['string']);
    }

    /**
     * @test
     */
    public function replaceVariablesInPhpStringReplacesClassConstantMarkersWithApproppriateConstants()
    {
        $settings = [
            'foo' => 'bar',
            'baz' => '%Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY%',
            'inspiring' => [
                'people' => [
                    'to' => '%Neos\Flow\Core\Bootstrap::MINIMUM_PHP_VERSION%',
                    'share' => '%Neos\Flow\Package\FlowPackageInterface::DIRECTORY_CLASSES%'
                ]
            ]
        ];
        $settingsPhpString = var_export($settings, true);

        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');

        self::assertSame(ConfigurationManager::CONFIGURATION_TYPE_POLICY, $settings['baz']);
        self::assertSame(Bootstrap::MINIMUM_PHP_VERSION, $settings['inspiring']['people']['to']);
        self::assertSame(FlowPackageInterface::DIRECTORY_CLASSES, $settings['inspiring']['people']['share']);
    }

    /**
     * @test
     */
    public function replaceVariablesInPhpStringReplacesEnvMarkersWithEnvironmentValues()
    {
        $envVarName = 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR';
        $envVarValue = 'NEOS_Flow_Tests_Unit_Configuration_ConfigurationManagerTest_MockEnvValue';

        putenv($envVarName . '=' . $envVarValue);

        $settings = [
            'foo' => 'bar',
            'bar' => '%env:' . $envVarName . '%',
            'baz' => '%env:' . $envVarName . '% inspiring people %env:' . $envVarName . '% to share',
            'inspiring' => [
                'people' => [
                    'to' => '%env:' . $envVarName . '%',
                    'share' => 'foo %env:' . $envVarName . '% bar'
                ]
            ]
        ];
        $settingsPhpString = var_export($settings, true);

        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');

        self::assertSame($envVarValue, $settings['bar']);
        self::assertSame($envVarValue . ' inspiring people ' . $envVarValue . ' to share', $settings['baz']);
        self::assertSame($envVarValue, $settings['inspiring']['people']['to']);
        self::assertSame('foo ' . $envVarValue . ' bar', $settings['inspiring']['people']['share']);

        putenv($envVarName);
    }

    public function replaceVariablesInPhpStringReplacesEnvMarkersDataProvider(): \Traversable
    {
        yield 'lower case env variables are not replaced' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env:neos_flow_test_unit_configuration_lower_case_environment_variable%', 'expectedResult' => '%env:neos_flow_test_unit_configuration_lower_case_environment_variable%'];
        yield 'non-existing environment variables evaluate to false' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env:NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%', 'expectedResult' => false];
        yield 'concatenating non-existing environment variables' => ['envVarName' => '', 'envVarValue' => '', 'setting' => 'prefix%env:NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%suffix', 'expectedResult' => 'prefixsuffix'];
        yield 'integer env variables are casted to string by default' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env:NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => '123'];

        yield 'invalid format skips replacement' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env(unknown):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => '%env(unknown):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%'];
        yield 'empty format skips replacement' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env():NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => '%env():NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%'];

        yield 'format int casts non-numeric env variable to 0' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'foo', 'setting' => '%env(int):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => 0];
        yield 'format int casts numeric env variable to its integer value' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env(int):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => 123];
        yield 'integer-casted values can be concatenated with strings' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env(int):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%suffix', 'expectedResult' => '123suffix'];
        yield 'integer-casted values can be concatenated with integers' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env(int):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%456', 'expectedResult' => '123456'];
        yield 'format int casts non-existing env variable to 0' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env(int):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%', 'expectedResult' => 0];

        yield 'format bool casts "1" to true' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '1', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => true];
        yield 'format bool casts "true" to true' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'true', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => true];
        yield 'format bool casts "false" to false' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'false', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => false];
        yield 'format bool casts "FALSE" to false' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'FALSE', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => false];
        yield 'format bool casts "0" to false' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '0', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => false];
        yield 'concatenating format bool, true' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'true', 'setting' => 'prefix%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%suffix', 'expectedResult' => 'prefix1suffix'];
        yield 'concatenating format bool, false' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '0', 'setting' => 'prefix%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%suffix', 'expectedResult' => 'prefixsuffix'];
        yield 'format bool casts non-existing env variable to false' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env(bool):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%', 'expectedResult' => false];

        yield 'format float casts string to 0.0' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => 'foo', 'setting' => '%env(float):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => 0.0];
        yield 'format float casts "1,4" to 1.0' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '1,4', 'setting' => '%env(float):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => 1.0];
        yield 'format float casts "1.5" to 1.5' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '1.5', 'setting' => '%env(float):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => 1.5];
        yield 'format float casts non-existing env variable to 0.0' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env(float):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%', 'expectedResult' => 0.0];

        yield 'format string casts numeric string' => ['envVarName' => 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR', 'envVarValue' => '123', 'setting' => '%env(string):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR%', 'expectedResult' => '123'];
        yield 'format string casts non-existing env variable to ""' => ['envVarName' => '', 'envVarValue' => '', 'setting' => '%env(string):NEOS_FLOW_TESTS_UNIT_CONFIGURATION_NON_EXISTING_ENVIRONMENT_VARIABLE%', 'expectedResult' => ''];
    }

    /**
     * @test
     * @dataProvider replaceVariablesInPhpStringReplacesEnvMarkersDataProvider
     */
    public function replaceVariablesInPhpStringReplacesEnvMarkersTests(string $envVarName, string $envVarValue, string $setting, $expectedResult): void
    {
        if ($envVarName !== '') {
            putenv($envVarName . '=' . $envVarValue);
        }
        $settingsPhpString = var_export(['setting' => $setting], true);
        $configurationManager = $this->getAccessibleConfigurationManager(['dummy']);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');

        self::assertSame($expectedResult, $settings['setting']);

        if ($envVarName !== '') {
            putenv($envVarName);
        }
    }

    /**
     * We expect that the context specific routes are loaded *first*
     *
     * @test
     */
    public function loadConfigurationForRoutesLoadsContextSpecificRoutesFirst()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageRoutesCallback', 'Testing/System1');

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);
        $configurationManager->_set('configurations', ['Settings' => ['Neos' => ['Flow' => ['mvc' => ['routes' => []]]]]]);

        $configurationSource = $configurationManager->_get('configurationSource');
        $routesLoader = new RoutesLoader($configurationSource, $configurationManager);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $routesLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedRoutesConfiguration = [
            [
                'name' => 'GlobalSubContextRoute1',
                'uriPattern' => 'globalSubContextRoute1'
            ],
            [
                'name' => 'GlobalSubContextRoute2',
                'uriPattern' => 'globalSubContextRoute2'
            ],
            // BEGIN SUBROUTES
            [
                'name' => 'GlobalContextRoute1 :: PackageSubContextRoute1',
                'uriPattern' => 'globalContextRoute1/packageSubContextRoute1'
            ],
            [
                'name' => 'GlobalContextRoute1 :: PackageSubContextRoute2',
                'uriPattern' => 'globalContextRoute1/packageSubContextRoute2'
            ],
            [
                'name' => 'GlobalContextRoute1 :: PackageContextRoute1',
                'uriPattern' => 'globalContextRoute1/packageContextRoute1'
            ],
            [
                'name' => 'GlobalContextRoute1 :: PackageContextRoute2',
                'uriPattern' => 'globalContextRoute1/packageContextRoute2'
            ],
            [
                'name' => 'GlobalContextRoute1 :: PackageRoute1',
                'uriPattern' => 'globalContextRoute1/packageRoute1'
            ],
            [
                'name' => 'GlobalContextRoute1 :: PackageRoute2',
                'uriPattern' => 'globalContextRoute1/packageRoute2'
            ],
            // END SUBROUTES
            [
                'name' => 'GlobalContextRoute2',
                'uriPattern' => 'globalContextRoute2'
            ],
            [
                'name' => 'GlobalRoute1',
                'uriPattern' => 'globalRoute1'
            ],
            [
                'name' => 'GlobalRoute2',
                'uriPattern' => 'globalRoute2'
            ]
        ];

        self::assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
    }

    /**
     * Callback for the above test.
     * @param string $filenameAndPath
     * @return array
     * @throws \Exception
     */
    public function packageRoutesCallback($filenameAndPath)
    {

        // The routes from the innermost context should be added FIRST, such that
        // they take precedence over more generic contexts
        $packageSubContextRoutes = [
            [
                'name' => 'PackageSubContextRoute1',
                'uriPattern' => 'packageSubContextRoute1'
            ],
            [
                'name' => 'PackageSubContextRoute2',
                'uriPattern' => 'packageSubContextRoute2'
            ],
        ];

        $packageContextRoutes = [
            [
                'name' => 'PackageContextRoute1',
                'uriPattern' => 'packageContextRoute1'
            ],
            [
                'name' => 'PackageContextRoute2',
                'uriPattern' => 'packageContextRoute2'
            ]
        ];

        $packageRoutes = [
            [
                'name' => 'PackageRoute1',
                'uriPattern' => 'packageRoute1'
            ],
            [
                'name' => 'PackageRoute2',
                'uriPattern' => 'packageRoute2'
            ]
        ];

        $globalSubContextRoutes = [
            [
                'name' => 'GlobalSubContextRoute1',
                'uriPattern' => 'globalSubContextRoute1'
            ],
            [
                'name' => 'GlobalSubContextRoute2',
                'uriPattern' => 'globalSubContextRoute2'
            ]
        ];

        $globalContextRoutes = [
            [
                'name' => 'GlobalContextRoute1',
                'uriPattern' => 'globalContextRoute1/<PackageSubroutes>',
                'subRoutes' => [
                    'PackageSubroutes' => [
                        'package' => 'Neos.Flow'
                    ]
                ],
            ],
            [
                'name' => 'GlobalContextRoute2',
                'uriPattern' => 'globalContextRoute2'
            ]
        ];

        $globalRoutes = [
            [
                'name' => 'GlobalRoute1',
                'uriPattern' => 'globalRoute1'
            ],
            [
                'name' => 'GlobalRoute2',
                'uriPattern' => 'globalRoute2'
            ]
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Routes': return $packageRoutes;
            case 'Flow/Configuration/Testing/Routes': return $packageContextRoutes;
            case 'Flow/Configuration/Testing/System1/Routes': return $packageSubContextRoutes;
            case FLOW_PATH_CONFIGURATION . 'Routes': return $globalRoutes;
            case FLOW_PATH_CONFIGURATION . 'Testing/Routes': return $globalContextRoutes;
            case FLOW_PATH_CONFIGURATION . 'Testing/System1/Routes': return $globalSubContextRoutes;
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }

    /**
     * @test
     */
    public function loadConfigurationForRoutesLoadsSubRoutesRecursively()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageSubRoutesCallback', 'Testing/System1');

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);

        $configurationSource = $configurationManager->_get('configurationSource');

        $settingsLoader = new SettingsLoader($configurationSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $routesLoader = new RoutesLoader($configurationSource, $configurationManager);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $routesLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedRoutesConfiguration = [
            [
                'name' => 'a :: b1 :: c1',
                'uriPattern' => 'a/b1/c1'
            ],
            [
                'name' => 'a :: b2 :: d1 :: c1',
                'uriPattern' => 'a/b2/d1/c1'
            ],
            [
                'name' => 'a :: b1 :: c2 :: e1',
                'uriPattern' => 'a/b1/c2/e1'
            ],
            [
                'name' => 'a :: b2 :: d1 :: c2 :: e1',
                'uriPattern' => 'a/b2/d1/c2/e1'
            ],
            [
                'name' => 'a :: b1 :: c2 :: e2',
                'uriPattern' => 'a/b1/c2/e2'
            ],
            [
                'name' => 'a :: b2 :: d1 :: c2 :: e2',
                'uriPattern' => 'a/b2/d1/c2/e2'
            ],
        ];

        self::assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
    }

    /**
     * Callback for the above test.
     * @param string $filenameAndPath
     * @return array
     */
    public function packageSubRoutesCallback($filenameAndPath)
    {
        $globalRoutes = [
            [
                'name' => 'a',
                'uriPattern' => 'a/<b>/<c>',
                'subRoutes' => [
                    'b' => [
                        'package' => 'Neos.Flow',
                        'suffix' => 'b'
                    ],
                    'c' => [
                        'package' => 'Neos.Flow',
                        'suffix' => 'c'
                    ]
                ]
            ]
        ];

        $subRoutesB = [
            [
                'name' => 'b1',
                'uriPattern' => 'b1'
            ],
            [
                'name' => 'b2',
                'uriPattern' => 'b2/<d>',
                'subRoutes' => [
                    'd' => [
                        'package' => 'Neos.Flow',
                        'suffix' => 'd'
                    ]
                ]
            ]
        ];

        $subRoutesC = [
            [
                'name' => 'c1',
                'uriPattern' => 'c1'
            ],
            [
                'name' => 'c2',
                'uriPattern' => 'c2/<e>',
                'subRoutes' => [
                    'e' => [
                        'package' => 'Neos.Flow',
                        'suffix' => 'e'
                    ]
                ]
            ]
        ];

        $subRoutesD = [
            [
                'name' => 'd1',
                'uriPattern' => 'd1'
            ]
        ];

        $subRoutesE = [
            [
                'name' => 'e1',
                'uriPattern' => 'e1'
            ],
            [
                'name' => 'e2',
                'uriPattern' => 'e2'
            ],
        ];

        switch ($filenameAndPath) {
            case FLOW_PATH_CONFIGURATION . 'Routes':
                return $globalRoutes;
            case 'Flow/Configuration/Routes.b':
                return $subRoutesB;
            case 'Flow/Configuration/Routes.c':
                return $subRoutesC;
            case 'Flow/Configuration/Routes.d':
                return $subRoutesD;
            case 'Flow/Configuration/Routes.e':
                return $subRoutesE;
            default:
                return [];
        }
    }

    /**
     * @test
     */
    public function loadConfigurationForRoutesIncludesSubRoutesFromSettings()
    {
        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnCallBack([$this, 'packageRoutesAndSettingsCallback']));

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType']);
        $configurationManager->_set('configurationSource', $mockYamlSource);

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);

        $settingsLoader = new SettingsLoader($mockYamlSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $routesLoader = new RoutesLoader($mockYamlSource, $configurationManager);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $routesLoader);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedRoutesConfiguration = [
            // ROUTES DEFINED IN ROUTES.YAML ALWAYS COME FIRST:
            [
                'name' => 'GlobalRoute1',
                'uriPattern' => 'globalRoute1'
            ],
            [
                'name' => 'GlobalRoute2',
                'uriPattern' => 'globalRoute2'
            ],
            // MERGED SUBROUTES FROM SETTINGS
            [
                'name' => 'Neos.Flow :: PackageRoute1',
                'uriPattern' => 'packageRoute1/some-value'
            ],
            [
                'name' => 'Neos.Flow :: PackageRoute2',
                'uriPattern' => 'packageRoute2'
            ],
        ];

        self::assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
    }

    /**
     * Callback for the above test.
     * @param string $filenameAndPath
     * @return array
     * @throws \Exception
     */
    public function packageRoutesAndSettingsCallback($filenameAndPath)
    {
        $packageRoutes = [
            [
                'name' => 'PackageRoute1',
                'uriPattern' => 'packageRoute1/<variable>'
            ],
            [
                'name' => 'PackageRoute2',
                'uriPattern' => 'packageRoute2'
            ]
        ];

        $globalRoutes = [
            [
                'name' => 'GlobalRoute1',
                'uriPattern' => 'globalRoute1'
            ],
            [
                'name' => 'GlobalRoute2',
                'uriPattern' => 'globalRoute2'
            ]
        ];

        $globalSettings = [
            'Neos' => [
                'Flow' => [
                    'mvc' => [
                        'routes' => [
                            'Neos.Flow' => [
                                'position' => 'start',
                                'suffix' => 'SomeSuffix',
                                'variables' => [
                                    'variable' => 'some-value'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Routes.SomeSuffix': return $packageRoutes;
            case 'Flow/Configuration/Testing/Routes.SomeSuffix': return [];
            case FLOW_PATH_CONFIGURATION . 'Routes': return $globalRoutes;
            case FLOW_PATH_CONFIGURATION . 'Testing/Routes': return [];

            case 'Flow/Configuration/Settings': return [];
            case 'Flow/Configuration/Testing/Settings': return [];
            case FLOW_PATH_CONFIGURATION . 'Settings': return $globalSettings;
            case FLOW_PATH_CONFIGURATION . 'Testing/Settings': return [];
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }

    /**
     * @test
     */
    public function loadConfigurationForRoutesThrowsExceptionIfSubRoutesContainCircularReferences()
    {
        $this->expectException(RecursionException::class);
        $mockSubRouteConfiguration =
            [
                'name' => 'SomeRouteOrSubRoute',
                'uriPattern' => '<PackageSubroutes>',
                'subRoutes' => [
                    'PackageSubroutes' => [
                        'package' => 'Neos.Flow'
                    ]
                ],
            ];
        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnValue([$mockSubRouteConfiguration]));

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType']);

        $settingsLoader = new SettingsLoader($mockYamlSource);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingsLoader);

        $routesLoader = new RoutesLoader($mockYamlSource, $configurationManager);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $routesLoader);

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);
        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);
    }

    /**
     * @test
     */
    public function mergeRoutesWithSubRoutesThrowsExceptionIfRouteRefersToNonExistingOrInactivePackages()
    {
        $this->expectException(ParseErrorException::class);
        $routesConfiguration = [
            [
                'name' => 'Welcome',
                'uriPattern' => '<WelcomeSubroutes>',
                'subRoutes' => [
                    'WelcomeSubroutes' => [
                        'package' => 'Welcome'
                    ]
                ]
            ]
        ];

        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnValue([$routesConfiguration]));

        $applicationContext = new ApplicationContext('Production');
        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType']);

        $mockRoutesLoader = $this->getAccessibleMock(RoutesLoader::class, [], [$mockYamlSource, $configurationManager], '', true, true, true, false, true);

        $mockPackages = $this->getMockPackages();
        $subRoutesRecursionLevel = 0;
        $mockRoutesLoader->_call('mergeRoutesWithSubRoutes', $mockPackages, $applicationContext, $routesConfiguration, $subRoutesRecursionLevel);
    }

    /**
     * @test
     */
    public function mergeRoutesWithSubRoutesRespectsSuffixSubRouteOption()
    {
        $mockRoutesConfiguration = [
            [
                'name' => 'SomeRoute',
                'uriPattern' => '<PackageSubroutes>',
                'subRoutes' => [
                    'PackageSubroutes' => [
                        'package' => 'Neos.Flow',
                        'suffix' => 'Foo'
                    ]
                ],
            ]
        ];

        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::atLeast(3))->method('load')->withConsecutive(['Flow/Configuration/Testing/System1/Routes.Foo'], ['Flow/Configuration/Testing/Routes.Foo'], ['Flow/Configuration/Routes.Foo'])->willReturn([]);

        $configurationManager = $this->getAccessibleConfigurationManager([]);

        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, function (array $packages, ApplicationContext $context) {
            return [];
        });

        $mockRoutesLoader = $this->getAccessibleMock(RoutesLoader::class, [], [$mockYamlSource, $configurationManager], '', true, true, true, false, true);

        $routeSettings = [];
        $routesConfiguration = $mockRoutesLoader->_call('includeSubRoutesFromSettings', $mockRoutesConfiguration, $routeSettings);
        $mockRoutesLoader->_call('mergeRoutesWithSubRoutes', $this->getMockPackages(), new ApplicationContext('Testing/System1'), $routesConfiguration);
    }

    /**
     * @test
     */
    public function buildSubrouteConfigurationsCorrectlyMergesRoutes()
    {
        $routesConfiguration = [
            [
                'name' => 'Welcome',
                'uriPattern' => '<WelcomeSubroutes>',
                'defaults' => [
                    '@package' => 'Welcome'
                ],
                'subRoutes' => [
                    'WelcomeSubroutes' => [
                        'package' => 'Welcome'
                    ]
                ],
                'routeParts' => [
                    'foo' => [
                        'bar' => 'baz',
                        'baz' => 'Xyz'
                    ]
                ],
                'toLowerCase' => true
            ]
        ];
        $subRoutesConfiguration = [
            [
                'name' => 'Standard route',
                'uriPattern' => 'flow/welcome',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => 'index'
                ]
            ],
            [
                'name' => 'Redirect',
                'uriPattern' => '',
                'defaults' => [
                    '@controller' => 'Standard',
                    '@action' => 'redirect'
                ],
                'routeParts' => [
                    'foo' => [
                        'bar' => 'overridden',
                        'new' => 'ZZZ'
                    ]
                ],
                'toLowerCase' => false,
                'appendExceedingArguments' => true
            ]
        ];
        $expectedResult = [
            [
                'name' => 'Welcome :: Standard route',
                'uriPattern' => 'flow/welcome',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => 'index'
                ],
                'routeParts' => [
                    'foo' => [
                        'bar' => 'baz',
                        'baz' => 'Xyz'
                    ]
                ],
                'toLowerCase' => true,
            ],
            [
                'name' => 'Welcome :: Redirect',
                'uriPattern' => '',
                'defaults' => [
                    '@package' => 'Welcome',
                    '@controller' => 'Standard',
                    '@action' => 'redirect'
                ],
                'routeParts' => [
                    'foo' => [
                        'bar' => 'overridden',
                        'baz' => 'Xyz',
                        'new' => 'ZZZ'
                    ]
                ],
                'toLowerCase' => false,
                'appendExceedingArguments' => true
            ]
        ];

        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();

        $configurationManager = $this->getAccessibleConfigurationManager([]);

        $mockRoutesLoader = $this->getAccessibleMock(RoutesLoader::class, [], [$mockYamlSource, $configurationManager], '', true, true, true, false, true);

        $actualResult = $mockRoutesLoader->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', []);

        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildSubrouteConfigurationsMergesSubRoutesAndProcessesPlaceholders()
    {
        $routesConfiguration = [
            [
                'name' => 'Welcome',
                'uriPattern' => 'welcome/<WelcomeSubroutes>',
                'defaults' => [
                    '@package' => 'Welcome',
                ],
            ]
        ];
        $subRouteOptions = [
            'package' => 'Welcome',
            'variables' => [
                'someVariable' => 'someValue',
                'someOtherVariable' => 'someOtherValue'
            ]
        ];
        $subRoutesConfiguration = [
            [
                'name' => 'Standard Route',
                'uriPattern' => '{foo}',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => '<someVariable>'
                ],
                'routeParts' => [
                    'foo' => [
                        'handler' => 'Some\RoutePart\Handler',
                        'options' => [
                            'someOption' => '<someOtherVariable>'
                        ],
                    ],
                ],
            ], [
                'name' => 'Fallback',
                'uriPattern' => '',
                'defaults' => [
                    '@controller' => 'Standard',
                    '@action' => 'redirect',
                    '--posts-paginator' => [
                      '@package' => '',
                      '@subpackage' => '',
                      '@controller' => '',
                      '@action' => '<someOtherVariable>',
                      'currentPage' => '1'
                    ]
                ],
            ]
        ];
        $expectedResult = [
            [
                'name' => 'Welcome :: Standard Route',
                'uriPattern' => 'welcome/{foo}',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => 'someValue'
                ],
                'routeParts' => [
                    'foo' => [
                        'handler' => 'Some\RoutePart\Handler',
                        'options' => [
                            'someOption' => 'someOtherValue'
                        ],
                    ],
                ],
            ], [
                'name' => 'Welcome :: Fallback',
                'uriPattern' => 'welcome',
                'defaults' => [
                    '@package' => 'Welcome',
                    '@controller' => 'Standard',
                    '@action' => 'redirect',
                    '--posts-paginator' => [
                        '@package' => '',
                        '@subpackage' => '',
                        '@controller' => '',
                        '@action' => 'someOtherValue',
                        'currentPage' => '1'
                    ]
                ],
            ]
        ];

        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();

        $configurationManager = $this->getAccessibleConfigurationManager([]);

        $mockRoutesLoader = $this->getAccessibleMock(RoutesLoader::class, [], [$mockYamlSource, $configurationManager], '', true, true, true, false, true);

        $actualResult = $mockRoutesLoader->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', $subRouteOptions);

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function buildSubrouteConfigurationsWontReplaceNonStringValues()
    {
        $routesConfiguration = [
            [
                'name' => 'Root',
                'uriPattern' => '<Subroutes>',
            ]
        ];
        $subRouteOptions = [
            'package' => 'Subroutes',
            'variables' => [
                'suffix' => '.html',
            ]
        ];
        $subRoutesConfiguration = [
            [
                'name' => 'SubRoute',
                'uriPattern' => '{foo}<suffix>',
                'routeParts' => [
                    'foo' => [
                        'handler' => 'Some\RoutePart\Handler',
                        'options' => [
                            'someOption' => true
                        ],
                    ],
                ],
            ]
        ];
        $expectedResult = [
            [
                'name' => 'Root :: SubRoute',
                'uriPattern' => '{foo}.html',
                'routeParts' => [
                    'foo' => [
                        'handler' => 'Some\RoutePart\Handler',
                        'options' => [
                            'someOption' => true
                        ],
                    ],
                ],
            ]
        ];

        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();

        $configurationManager = $this->getAccessibleConfigurationManager([]);

        $mockRoutesLoader = $this->getAccessibleMock(RoutesLoader::class, [], [$mockYamlSource, $configurationManager], '', true, true, true, false, true);

        $actualResult = $mockRoutesLoader->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'Subroutes', $subRouteOptions);

        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * We expect that the context specific Views configurations are loaded *first*
     *
     * @test
     */
    public function loadConfigurationForViewsLoadsAppendsAllConfigurations()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageViewConfigurationsCallback', 'Testing/System1');

        $configurationSource = $configurationManager->_get('configurationSource');
        $configurationManager->registerConfigurationType('Views', new AppendLoader($configurationSource, 'Views'));

        $configurationManager->setPackages($this->getMockPackages());

        $configurationManager->_call('loadConfiguration', 'Views', $this->getMockPackages());

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedRoutesConfiguration = [
            [
                'requestFilter' => 'RequestFilterFromPackage',
            ],
            [
                'requestFilter' => 'RequestFilterFromGlobal',
            ],
            [
                'requestFilter' => 'RequestFilterFromPackageContext',
            ],
            [
                'requestFilter' => 'RequestFilterFromGlobalContext',
            ],
            [
                'requestFilter' => 'RequestFilterFromPackageSubContext',
            ],
            [
                'requestFilter' => 'RequestFilterFromGlobalSubContext',
            ],
        ];

        self::assertSame($expectedRoutesConfiguration, $actualConfigurations['Views']);
    }


    /**
     * Callback for the Views test above.
     *
     * @param string $filenameAndPath
     * @throws \Exception
     * @return array
     */
    public function packageViewConfigurationsCallback($filenameAndPath)
    {
        $packageSubContextViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromPackageSubContext',
            ],
        ];

        $packageContextViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromPackageContext',
            ],
        ];

        $packageViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromPackage',
            ],
        ];

        $globalSubContextViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromGlobalSubContext',
            ],
        ];

        $globalContextViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromGlobalContext',
            ],
        ];

        $globalViewConfigurations = [
            [
                'requestFilter' => 'RequestFilterFromGlobal',
            ],
        ];

        switch ($filenameAndPath) {
            case 'Flow/Configuration/Views': return $packageViewConfigurations;
            case 'Flow/Configuration/Testing/Views': return $packageContextViewConfigurations;
            case 'Flow/Configuration/Testing/System1/Views': return $packageSubContextViewConfigurations;
            case FLOW_PATH_CONFIGURATION . 'Views': return $globalViewConfigurations;
            case FLOW_PATH_CONFIGURATION . 'Testing/Views': return $globalContextViewConfigurations;
            case FLOW_PATH_CONFIGURATION . 'Testing/System1/Views': return $globalSubContextViewConfigurations;
            default:
                throw new \Exception('Unexpected filename: ' . $filenameAndPath);
        }
    }

    /**
     * @test
     */
    public function loadingConfigurationOfCustomConfigurationTypeWorks()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('loadingConfigurationOfCustomConfigurationTypeCallback', 'Testing');

        $customLoader = new class implements LoaderInterface {
            public function load(array $packages, ApplicationContext $context): array
            {
                return ['SomeKey' => 'SomeValue'];
            }
        };
        $configurationManager->registerConfigurationType('MyCustomConfiguration', $customLoader);

        $configurationManager->_call('loadConfiguration', 'MyCustomConfiguration', $this->getMockPackages());
        $configuration = $configurationManager->getConfiguration('MyCustomConfiguration');
        self::assertArrayHasKey('SomeKey', $configuration);
    }

    /**
     * A callback as stand in configruation source for above test.
     *
     * @param string $filenameAndPath
     * @return array
     */
    public function loadingConfigurationOfCustomConfigurationTypeCallback($filenameAndPath)
    {
        return [
            'SomeKey' => 'SomeValue'
        ];
    }

    /**
     * @param ApplicationContext|null $customContext
     * @param array $methods
     * @return ConfigurationManager|MockObject
     */
    protected function getAccessibleConfigurationManager(array $methods = [], ApplicationContext $customContext = null)
    {
        return $this->getAccessibleMock(ConfigurationManager::class, $methods, [$customContext ?? $this->mockContext]);
    }

    /**
     * @param string $configurationSourceCallbackName
     * @param string $contextName
     * @return ConfigurationManager
     */
    protected function getConfigurationManagerWithFlowPackage($configurationSourceCallbackName, $contextName)
    {
        $mockYamlSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockYamlSource->expects(self::any())->method('load')->will(self::returnCallBack([$this, $configurationSourceCallbackName]));

        $configurationManager = $this->getAccessibleConfigurationManager(['postProcessConfigurationType', 'includeSubRoutesFromSettings'], new ApplicationContext($contextName));
        $configurationManager->_set('configurationSource', $mockYamlSource);

        return $configurationManager;
    }

    /**
     * @return array
     */
    protected function getMockPackages()
    {
        $mockPackageFlow = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackageFlow->expects(self::any())->method('getConfigurationPath')->will(self::returnValue('Flow/Configuration/'));
        $mockPackageFlow->expects(self::any())->method('getPackageKey')->will(self::returnValue('Neos.Flow'));

        $mockPackages = [
            'Neos.Flow' => $mockPackageFlow
        ];

        return $mockPackages;
    }
}
