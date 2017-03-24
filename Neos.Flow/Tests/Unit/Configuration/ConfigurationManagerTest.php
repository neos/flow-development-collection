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
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the configuration manager
 */
class ConfigurationManagerTest extends UnitTestCase
{
    /**
     * @var ApplicationContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockContext;

    public function setUp()
    {
        $this->mockContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function getConfigurationForSettingsLoadsConfigurationIfNecessary()
    {
        $initialConfigurations = [
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => [],
        ];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [new ApplicationContext('Testing')], '', false);
        $configurationManager->_set('configurations', $initialConfigurations);

        $configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [], '', false);
        $configurationManager->_set('configurations', $configurations);

        $actualConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
        $this->assertSame($expectedConfiguration, $actualConfiguration);
    }

    /**
     * @test
     */
    public function getConfigurationForTypeSettingsLoadsConfigurationIfNecessary()
    {
        $packages = ['SomePackage' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock()];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', [ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => []]);
        $configurationManager->setPackages($packages);
        $configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packages);

        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
    }

    /**
     * @test
     */
    public function getConfigurationForTypeObjectLoadsConfiguration()
    {
        $packages = ['SomePackage' => $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock()];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', [ConfigurationManager::CONFIGURATION_TYPE_OBJECTS => []]);
        $configurationManager->setPackages($packages);
        $configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $packages);

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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', $initialConfigurations);

        $configurationManager->expects($this->at(0))->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_CACHES);

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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', $expectedConfigurations);
        $configurationManager->expects($this->never())->method('loadConfiguration');

        foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
            $actualConfiguration = $configurationManager->getConfiguration($configurationType);
            $this->assertSame($expectedConfiguration, $actualConfiguration);
        }
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function gettingUnregisteredConfigurationTypeFails()
    {
        $expectedConfigurations = [
            'Custom' => ['custom'],
        ];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', $expectedConfigurations);
        $configurationManager->expects($this->never())->method('loadConfiguration');

        foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
            $actualConfiguration = $configurationManager->getConfiguration($configurationType);
            $this->assertSame($expectedConfiguration, $actualConfiguration);
        }
    }

    /**
     * @test
     */
    public function getConfigurationForCustomConfigurationUsingSettingsProcessingReturnsRespectiveConfigurationArray()
    {
        $expectedConfigurations = [
            'Custom' => ['custom'],
        ];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->_set('configurations', $expectedConfigurations);
        $configurationManager->expects($this->never())->method('loadConfiguration');

        foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
            $configurationManager->registerConfigurationType($configurationType, ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_SETTINGS);
            $actualConfiguration = $configurationManager->getConfiguration($configurationType);
            $this->assertSame($expectedConfiguration, $actualConfiguration);
        }

        $expectedConfigurationTypes = ['Caches', 'Objects', 'Routes', 'Policy', 'Settings', 'Custom'];
        $this->assertEquals($expectedConfigurationTypes, $configurationManager->getAvailableConfigurationTypes());
    }

    /**
     * @expectedException \Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException
     * @test
     */
    public function getConfigurationThrowsExceptionOnInvalidConfigurationType()
    {
        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->getConfiguration('Nonsense');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function registerConfigurationTypeThrowsExceptionOnInvalidConfigurationProcessingType()
    {
        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['loadConfiguration'], [], '', false);
        $configurationManager->registerConfigurationType('MyCustomType', 'Nonsense');
    }

    /**
     * @test
     */
    public function loadConfigurationOverridesSettingsByContext()
    {
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback([$this, 'packageSettingsCallback']));

        $mockPackageA = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackageA->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageA/Configuration/'));
        $mockPackageA->expects($this->any())->method('getPackageKey')->will($this->returnValue('PackageA'));

        $mockPackages = [
            'PackageA' => $mockPackageA,
        ];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration'], [new ApplicationContext('Testing')]);
        $configurationManager->_set('configurationSource', $mockConfigurationSource);

        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

        $actualConfigurations = $configurationManager->_get('configurations');
        $expectedSettings = [
            'foo' => 'D',
            'bar' => 'A'
        ];

        $this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['PackageA']);
    }

    /**
     * @test
     */
    public function loadConfigurationOverridesGlobalSettingsByContext()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageSettingsCallback', 'Testing/System1');
        $mockPackages = $this->getMockPackages();

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

        $this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
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

        $this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_OBJECTS]);
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

        $this->assertSame($expectedCachesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_CACHES]);
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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration', 'constructConfigurationCachePath', 'refreshConfiguration'], [], '', false);
        $configurationManager->expects($this->any())->method('constructConfigurationCachePath')->willReturn('notfound.php', $cachedConfigurationsPathAndFilename);
        $configurationManager->_set('configurations', ['foo' => 'untouched']);
        $configurationManager->_call('loadConfigurationCache');
        $this->assertSame(['foo' => 'untouched'], $configurationManager->_get('configurations'));

        $configurationManager->_call('loadConfigurationCache');
        $this->assertSame(['bar' => 'touched'], $configurationManager->_get('configurations'));
    }

    /**
     * @test
     */
    public function loadConfigurationCorrectlyMergesSettings()
    {
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback([$this, 'packageSettingsCallback']));

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration'], [new ApplicationContext('Testing')]);
        $configurationManager->_set('configurationSource', $mockConfigurationSource);

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
        $this->assertEquals($expectedConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration', 'constructConfigurationCachePath'], [], '', false);
        $configurationManager->setTemporaryDirectoryPath($temporaryDirectoryPath);
        $configurationManager->expects($this->any())->method('constructConfigurationCachePath')->willReturn($cachedConfigurationsPathAndFilename);
        $configurationManager->_set('configurations', $mockConfigurations);
        $configurationManager->_set('unprocessedConfiguration', $mockConfigurations);
        $configurationManager->_set('configurationTypes', [
            ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array(
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_ROUTES,
                'allowSplitSource' => false
            ),
            ConfigurationManager::CONFIGURATION_TYPE_CACHES => array(
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
                'allowSplitSource' => false
            ),
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(
                'processingType' => ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
                'allowSplitSource' => false
            ),
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
        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [], '', false);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $this->assertContains("'baz' => (defined('PHP_VERSION') ? constant('PHP_VERSION') : null)", $processedPhpString);
        $this->assertContains("'to' => (defined('FLOW_PATH_ROOT') ? constant('FLOW_PATH_ROOT') : null)", $processedPhpString);
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

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [], '', false);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');
        $this->assertInternalType('integer', $settings['anIntegerConstant']);
        $this->assertSame(PHP_VERSION_ID, $settings['anIntegerConstant']);

        $this->assertInternalType('string', $settings['casted']['to']['string']);
        $this->assertSame('Version id is ' . PHP_VERSION_ID, $settings['casted']['to']['string']);
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
                    'share' => '%Neos\Flow\Package\PackageInterface::DIRECTORY_CLASSES%'
                ]
            ]
        ];
        $settingsPhpString = var_export($settings, true);

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [], '', false);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');

        $this->assertSame(ConfigurationManager::CONFIGURATION_TYPE_POLICY, $settings['baz']);
        $this->assertSame(Bootstrap::MINIMUM_PHP_VERSION, $settings['inspiring']['people']['to']);
        $this->assertSame(PackageInterface::DIRECTORY_CLASSES, $settings['inspiring']['people']['share']);
    }

    /**
     * @test
     */
    public function replaceVariablesInPhpStringReplacesEnvMarkersWithEnvironmentValues()
    {
        $envVarName = 'NEOS_FLOW_TESTS_UNIT_CONFIGURATION_CONFIGURATIONMANAGERTEST_MOCKENVVAR';
        $envVarValue = 'NEOS_Flow_Tests_Unit_Configuration_ConfigurationManagerTest_MockEnvValue';

        putenv($envVarName . '=' . $envVarValue);

        $settings = array(
            'foo' => 'bar',
            'baz' => '%env:' . $envVarName . '%',
            'inspiring' => array(
                'people' => array(
                    'to' => '%env:' . $envVarName . '%',
                    'share' => 'foo %env:' . $envVarName . '% bar'
                )
            )
        );
        $settingsPhpString = var_export($settings, true);

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [], '', false);
        $processedPhpString = $configurationManager->_call('replaceVariablesInPhpString', $settingsPhpString);
        $settings = eval('return ' . $processedPhpString . ';');

        $this->assertSame($envVarValue, $settings['baz']);
        $this->assertSame($envVarValue, $settings['inspiring']['people']['to']);
        $this->assertSame('foo ' . $envVarValue . ' bar', $settings['inspiring']['people']['share']);

        putenv($envVarName);
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

        $this->assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
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

        $this->assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
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
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback([$this, 'packageRoutesAndSettingsCallback']));

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration'], [new ApplicationContext('Testing')]);
        $configurationManager->_set('configurationSource', $mockConfigurationSource);

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);
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

        $this->assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
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
     * @expectedException \Neos\Flow\Configuration\Exception\RecursionException
     */
    public function loadConfigurationForRoutesThrowsExceptionIfSubRoutesContainCircularReferences()
    {
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
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->any())->method('load')->will($this->returnValue([$mockSubRouteConfiguration]));

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration'], [new ApplicationContext('Production')]);
        $configurationManager->injectConfigurationSource($mockConfigurationSource);

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);
        $configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Configuration\Exception\ParseErrorException
     */
    public function mergeRoutesWithSubRoutesThrowsExceptionIfRouteRefersToNonExistingOrInactivePackages()
    {
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
        $subRoutesConfiguration = [];

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [new ApplicationContext('Testing')]);
        $configurationManager->_callRef('mergeRoutesWithSubRoutes', $routesConfiguration, $subRoutesConfiguration);
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
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->at(0))->method('load')->with('Flow/Configuration/Testing/System1/Routes.Foo')->will($this->returnValue([]));
        $mockConfigurationSource->expects($this->at(1))->method('load')->with('Flow/Configuration/Testing/Routes.Foo')->will($this->returnValue([]));
        $mockConfigurationSource->expects($this->at(2))->method('load')->with('Flow/Configuration/Routes.Foo')->will($this->returnValue([]));

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration'], [new ApplicationContext('Testing/System1')]);
        $configurationManager->_set('configurationSource', $mockConfigurationSource);

        $mockPackages = $this->getMockPackages();
        $configurationManager->setPackages($mockPackages);
        $configurationManager->_callRef('mergeRoutesWithSubRoutes', $mockRoutesConfiguration);
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
        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [new ApplicationContext('Testing')]);
        $actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', []);

        $this->assertEquals($expectedResult, $actualResult);
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
                'someVariable' => 'someValue'
            ]
        ];
        $subRoutesConfiguration = [
            [
                'name' => 'Standard Route',
                'uriPattern' => 'foo',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => '<someVariable>'
                ]
            ],
            [
                'name' => 'Fallback',
                'uriPattern' => '',
                'defaults' => [
                    '@controller' => 'Standard',
                    '@action' => 'redirect'
                ],
            ]
        ];
        $expectedResult = [
            [
                'name' => 'Welcome :: Standard Route',
                'uriPattern' => 'welcome/foo',
                'defaults' => [
                    '@package' => 'OverriddenPackage',
                    '@controller' => 'Standard',
                    '@action' => 'someValue'
                ],
            ],
            [
                'name' => 'Welcome :: Fallback',
                'uriPattern' => 'welcome',
                'defaults' => [
                    '@package' => 'Welcome',
                    '@controller' => 'Standard',
                    '@action' => 'redirect'
                ],
            ]
        ];
        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['dummy'], [new ApplicationContext('Testing')]);
        $actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', $subRouteOptions);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * We expect that the context specific Views configurations are loaded *first*
     *
     * @test
     */
    public function loadConfigurationForViewsLoadsAppendsAllConfigurations()
    {
        $configurationManager = $this->getConfigurationManagerWithFlowPackage('packageViewConfigurationsCallback', 'Testing/System1');
        $configurationManager->registerConfigurationType('Views', ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
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

        $this->assertSame($expectedRoutesConfiguration, $actualConfigurations['Views']);
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
     * @param string $configurationSourceCallbackName
     * @param string $contextName
     * @return ConfigurationManager
     */
    protected function getConfigurationManagerWithFlowPackage($configurationSourceCallbackName, $contextName)
    {
        $mockConfigurationSource = $this->getMockBuilder(YamlSource::class)->setMethods(['load', 'save'])->getMock();
        $mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback([$this, $configurationSourceCallbackName]));

        $configurationManager = $this->getAccessibleMock(ConfigurationManager::class, ['postProcessConfiguration', 'includeSubRoutesFromSettings'], [new ApplicationContext($contextName)]);
        $configurationManager->_set('configurationSource', $mockConfigurationSource);

        return $configurationManager;
    }

    /**
     * @return array
     */
    protected function getMockPackages()
    {
        $mockPackageFlow = $this->getMockBuilder(Package::class)->disableOriginalConstructor()->getMock();
        $mockPackageFlow->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('Flow/Configuration/'));
        $mockPackageFlow->expects($this->any())->method('getPackageKey')->will($this->returnValue('Neos.Flow'));

        $mockPackages = [
            'Neos.Flow' => $mockPackageFlow
        ];

        return $mockPackages;
    }
}
