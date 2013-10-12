<?php
namespace TYPO3\Flow\Tests\Unit\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\ApplicationContext;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the configuration manager
 */
class ConfigurationManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getConfigurationForSettingsLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(new ApplicationContext('Testing')), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
		$configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo');
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeSettingsReturnsRespectiveConfigurationArray() {
		$expectedConfiguration = array('foo' => 'bar');
		$configurations = array(
			ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(
				'SomePackage' => $expectedConfiguration
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);

		$actualConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeSettingsLoadsConfigurationIfNecessary() {
		$packages = array('SomePackage' => $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packages);

		$configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeObjectLoadsConfiguration() {
		$packages = array('SomePackage' => $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $packages);

		$configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'SomePackage');
	}

	/**
	 * @test
	 */
	public function getConfigurationForRoutesAndCachesLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('foo' => 'bar'),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->at(0))->method('loadConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_CACHES);

		$configurationTypes = array(
			ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
			ConfigurationManager::CONFIGURATION_TYPE_CACHES
		);
		foreach ($configurationTypes as $configurationType) {
			$configurationManager->getConfiguration($configurationType);
		}
	}

	/**
	 * @test
	 */
	public function getConfigurationForRoutesAndCachesReturnsRespectiveConfigurationArray() {
		$expectedConfigurations = array(
			ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches')
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $expectedConfigurations);
		$configurationManager->expects($this->never())->method('loadConfiguration');

		foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
			$actualConfiguration = $configurationManager->getConfiguration($configurationType);
			$this->assertSame($expectedConfiguration, $actualConfiguration);
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException
	 */
	public function gettingUnregisteredConfigurationTypeFails() {
		$expectedConfigurations = array(
			'Custom' => array('custom'),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
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
	public function getConfigurationForCustomConfigurationUsingSettingsProcessingReturnsRespectiveConfigurationArray() {
		$expectedConfigurations = array(
			'Custom' => array('custom'),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $expectedConfigurations);
		$configurationManager->expects($this->never())->method('loadConfiguration');

		foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
			$configurationManager->registerConfigurationType($configurationType, ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_SETTINGS);
			$actualConfiguration = $configurationManager->getConfiguration($configurationType);
			$this->assertSame($expectedConfiguration, $actualConfiguration);
		}

		$expectedConfigurationTypes = array('Caches', 'Objects', 'Routes', 'Policy', 'Settings', 'Custom');
		$this->assertEquals($expectedConfigurationTypes, $configurationManager->getAvailableConfigurationTypes());
	}

	/**
	 * @expectedException \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException
	 * @test
	 */
	public function getConfigurationThrowsExceptionOnInvalidConfigurationType() {
		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->getConfiguration('Nonsense');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @test
	 */
	public function registerConfigurationTypeThrowsExceptionOnInvalidConfigurationProcessingType() {
		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->registerConfigurationType('MyCustomType', 'Nonsense');
	}

	/**
	 * @test
	 */
	public function loadConfigurationOverridesSettingsByContext() {
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$mockPackageA = $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE);
		$mockPackageA->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageA/Configuration/'));
		$mockPackageA->expects($this->any())->method('getPackageKey')->will($this->returnValue('PackageA'));

		$mockPackages = array(
			'PackageA' => $mockPackageA,
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(new ApplicationContext('Testing')));
		$configurationManager->_set('configurationSource', $mockConfigurationSource);

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'foo' => 'D',
			'bar' => 'A'
		);

		$this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['PackageA']);
	}

	/**
	 * @test
	 */
	public function loadConfigurationOverridesGlobalSettingsByContext() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageSettingsCallback', 'Testing/System1');
		$mockPackages = $this->getMockPackages();

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'TYPO3' => array(
				'Flow' => array(
					'ex1' => 'global',
					'foo' => 'quux',
					'example' => 'fromTestingSystem1',
					'core' => array('context' => 'Testing/System1'),
				),
				'Testing' => array(
					'filters' => array()
				)
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
	}

	/**
	 * Callback for the above test.
	 *
	 */
	public function packageSettingsCallback() {
		$filenameAndPath = func_get_arg(0);

		$settingsFlow = array(
			'TYPO3' => array(
				'Flow' => array(
					'ex1' => 'global',
					'foo' => 'global stuff'
				),
				'Testing' => array(
					'filters' => array(
						'foo' => 'bar'
					)
				)
			)
		);

		$settingsFlowTesting = array(
			'TYPO3' => array(
				'Flow' => array(
					'foo' => 'quux',
					'example' => 'fromTesting'
				),
				'Testing' => array(
					'filters' => array()
				)
			)
		);

		$settingsFlowTestingSystem1 = array(
			'TYPO3' => array(
				'Flow' => array(
					'foo' => 'quux',
					'example' => 'fromTestingSystem1'
				)
			)
		);

		$settingsA = array(
			'PackageA' => array(
				'foo' => 'A',
				'bar' => 'A'
			)
		);

		$settingsB = array(
			'PackageA' => array(
				'bar' => 'B'
			),
			'PackageB' => array(
				'foo' => 'B',
				'bar' => 'B'
			)
		);

		$settingsC = array(
			'PackageA' => array(
				'bar' => 'C'
			),
			'PackageC' => array(
				'baz' => 'C'
			)
		);

		$settingsATesting = array(
			'PackageA' => array(
				'foo' => 'D'
			)
		);

		$globalSettings = array(
			'TYPO3' => arraY(
				'Flow' => array(
					'foo' => 'bar'
				)
			)
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Settings' : return $settingsFlow;
			case 'Flow/Configuration/SomeContext/Settings' : return array();
			case 'Flow/Configuration/Testing/Settings' : return $settingsFlowTesting;
			case 'Flow/Configuration/Testing/System1/Settings' : return $settingsFlowTestingSystem1;

			case 'PackageA/Configuration/Settings' : return $settingsA;
			case 'PackageA/Configuration/SomeContext/Settings' : return array();
			case 'PackageA/Configuration/Testing/Settings' : return $settingsATesting;
			case 'PackageB/Configuration/Settings' : return $settingsB;
			case 'PackageB/Configuration/SomeContext/Settings' : return array();
			case 'PackageB/Configuration/Testing/Settings' : return array();
			case 'PackageC/Configuration/Settings' : return $settingsC;
			case 'PackageC/Configuration/SomeContext/Settings' : return array();
			case 'PackageC/Configuration/Testing/Settings' : return array();

			case FLOW_PATH_CONFIGURATION . 'Settings' : return $globalSettings;
			case FLOW_PATH_CONFIGURATION . 'SomeContext/Settings' : return array();
			case FLOW_PATH_CONFIGURATION . 'Testing/Settings' : return array();
			case FLOW_PATH_CONFIGURATION . 'Testing/System1/Settings' : return array();
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationForObjectsOverridesConfigurationByContext() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageObjectsCallback', 'Testing/System1');
		$mockPackages = $this->getMockPackages();

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'TYPO3.Flow' => array(
				'TYPO3\Flow\SomeClass' => array(
					'className' => 'Bar',
					'configPackageObjects' => 'correct',
					'configGlobalObjects' => 'correct',
					'configPackageContextObjects' => 'correct',
					'configGlobalContextObjects' => 'correct',
					'configPackageSubContextObjects' => 'correct',
					'configGlobalSubContextObjects' => 'correct',
				)
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_OBJECTS]);
	}

	/**
	 * Callback for the above test.
	 */
	public function packageObjectsCallback() {
		$filenameAndPath = func_get_arg(0);

			// We expect the following overriding order:
			// - $packageObjects
			// - $globalObjects
			// - $packageContextObjects
			// - $globalContextObjects
			// - $packageSubContextObjects
			// - $globalSubContextObjects
		$packageObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'className' => 'Foo',
				'configPackageObjects' => 'correct',

				'configGlobalObjects' => 'overriddenWronglyFromPackageObjects',
				'configPackageContextObjects' => 'overriddenWronglyFromPackageObjects',
				'configGlobalContextObjects' => 'overriddenWronglyFromPackageObjects',
				'configPackageSubContextObjects' => 'overriddenWronglyFromPackageObjects',
				'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageObjects',
			)
		);

		$globalObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'configGlobalObjects' => 'correct',

				'configPackageContextObjects' => 'overriddenWronglyFromGlobalObjects',
				'configGlobalContextObjects' => 'overriddenWronglyFromGlobalObjects',
				'configPackageSubContextObjects' => 'overriddenWronglyFromGlobalObjects',
				'configGlobalSubContextObjects' => 'overriddenWronglyFromGlobalObjects',
			)
		);

		$packageContextObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'className' => 'Bar',

				'configPackageContextObjects' => 'correct',

				'configGlobalContextObjects' => 'overriddenWronglyFromPackageContextObjects',
				'configPackageSubContextObjects' => 'overriddenWronglyFromPackageContextObjects',
				'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageContextObjects',
			)
		);

		$globalContextObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'configGlobalContextObjects' => 'correct',

				'configPackageSubContextObjects' => 'overriddenWronglyFromGlobalContextObjects',
				'configGlobalSubContextObjects' => 'overriddenWronglyFromGlobalContextObjects',
			)
		);

		$packageSubContextObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'configPackageSubContextObjects' => 'correct',

				'configGlobalSubContextObjects' => 'overriddenWronglyFromPackageSubContextObjects',
			)
		);

		$globalSubContextObjects = array(
			'TYPO3\Flow\SomeClass' => array(
				'configGlobalSubContextObjects' => 'correct',
			)
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Objects' : return $packageObjects;
			case 'Flow/Configuration/Testing/Objects' : return $packageContextObjects;
			case 'Flow/Configuration/Testing/System1/Objects' : return $packageSubContextObjects;
			case FLOW_PATH_CONFIGURATION . 'Objects' : return $globalObjects;
			case FLOW_PATH_CONFIGURATION . 'Testing/Objects' : return $globalContextObjects;
			case FLOW_PATH_CONFIGURATION . 'Testing/System1/Objects' : return $globalSubContextObjects;
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}


	/**
	 * @test
	 */
	public function loadConfigurationForCachesOverridesConfigurationByContext() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageCachesCallback', 'Testing/System1');
		$mockPackages = $this->getMockPackages();

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_CACHES, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedCachesConfiguration = array(
			'TYPO3_Flow_SomeCache' => array(
				'configPackageCaches' => 'correct',
				'configGlobalCaches' => 'correct',
				'configPackageContextCaches' => 'correct',
				'configGlobalContextCaches' => 'correct',
				'configPackageSubContextCaches' => 'correct',
				'configGlobalSubContextCaches' => 'correct',
			)
		);

		$this->assertSame($expectedCachesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_CACHES]);
	}

	/**
	 * Callback for the above test.
	 */
	public function packageCachesCallback() {
		$filenameAndPath = func_get_arg(0);

			// We expect the following overriding order:
			// - $packageCaches
			// - $globalCaches
			// - $packageContextCaches
			// - $globalContextCaches
			// - $packageSubContextCaches
			// - $globalSubContextCaches
		$packageCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configPackageCaches' => 'correct',

				'configGlobalCaches' => 'overriddenWronglyFromPackageCaches',
				'configPackageContextCaches' => 'overriddenWronglyFromPackageCaches',
				'configGlobalContextCaches' => 'overriddenWronglyFromPackageCaches',
				'configPackageSubContextCaches' => 'overriddenWronglyFromPackageCaches',
				'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageCaches',
			)
		);

		$globalCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configGlobalCaches' => 'correct',

				'configPackageContextCaches' => 'overriddenWronglyFromGlobalCaches',
				'configGlobalContextCaches' => 'overriddenWronglyFromGlobalCaches',
				'configPackageSubContextCaches' => 'overriddenWronglyFromGlobalCaches',
				'configGlobalSubContextCaches' => 'overriddenWronglyFromGlobalCaches',
			)
		);

		$packageContextCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configPackageContextCaches' => 'correct',

				'configGlobalContextCaches' => 'overriddenWronglyFromPackageContextCaches',
				'configPackageSubContextCaches' => 'overriddenWronglyFromPackageContextCaches',
				'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageContextCaches',
			)
		);

		$globalContextCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configGlobalContextCaches' => 'correct',

				'configPackageSubContextCaches' => 'overriddenWronglyFromGlobalContextCaches',
				'configGlobalSubContextCaches' => 'overriddenWronglyFromGlobalContextCaches',
			)
		);

		$packageSubContextCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configPackageSubContextCaches' => 'correct',

				'configGlobalSubContextCaches' => 'overriddenWronglyFromPackageSubContextCaches',
			)
		);

		$globalSubContextCaches = array(
			'TYPO3_Flow_SomeCache' => array(
				'configGlobalSubContextCaches' => 'correct',
			)
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Caches' : return $packageCaches;
			case 'Flow/Configuration/Testing/Caches' : return $packageContextCaches;
			case 'Flow/Configuration/Testing/System1/Caches' : return $packageSubContextCaches;
			case FLOW_PATH_CONFIGURATION . 'Caches' : return $globalCaches;
			case FLOW_PATH_CONFIGURATION . 'Testing/Caches' : return $globalContextCaches;
			case FLOW_PATH_CONFIGURATION . 'Testing/System1/Caches' : return $globalSubContextCaches;
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationCacheLoadsConfigurationsFromCacheIfACacheFileExists() {
		vfsStream::setup('Flow');

		$configurationsCode = <<< "EOD"
<?php
return array('bar' => 'touched');
?>
EOD;

		$includeCachedConfigurationsPathAndFilename = vfsStream::url('Flow/IncludeCachedConfigurations.php');
		file_put_contents($includeCachedConfigurationsPathAndFilename, $configurationsCode);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);

		$configurationManager->_set('includeCachedConfigurationsPathAndFilename', 'notfound.php');
		$configurationManager->_set('configurations', array('foo' => 'untouched'));
		$configurationManager->_call('loadConfigurationCache');
		$this->assertSame(array('foo' => 'untouched'), $configurationManager->_get('configurations'));

		$configurationManager->_set('includeCachedConfigurationsPathAndFilename', $includeCachedConfigurationsPathAndFilename);
		$configurationManager->_call('loadConfigurationCache');
		$this->assertSame(array('bar' => 'touched'), $configurationManager->_get('configurations'));
	}

	/**
	 * @test
	 */
	public function loadConfigurationCorrectlyMergesSettings() {
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(new ApplicationContext('Testing')));
		$configurationManager->_set('configurationSource', $mockConfigurationSource);

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array());

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedConfiguration = array(
			'TYPO3' => array(
				'Flow' => array(
					'foo' => 'bar',
					'core' => array('context' => 'Testing')
				)
			)
		);
		$this->assertEquals($expectedConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
	}

	/**
	 * Callback for the above test.
	 */
	public function packagePolicyCallback() {
		$filenameAndPath = func_get_arg(0);

		$packagePolicy = array(
			'roles' => array(
				'Customer' => array(),
				'Expert' => array('Customer')
			),
			'acls' => array(
				'Everybody' => array(),
				'Anonymous' => array(),
				'Customer' => array(),
				'Expert' => array(),
			)
		);

		$contextPolicy = array(
			'roles' => array()
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Policy' : return $packagePolicy;
			case 'Flow/Configuration/Testing/Policy' : return $contextPolicy;
			case FLOW_PATH_CONFIGURATION . 'Policy' : return array();
			case FLOW_PATH_CONFIGURATION . 'Testing/Policy' : return array();
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationCorrectlyMergesPolicies() {
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packagePolicyCallback')));

		$mockPackageFlow = $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('Flow/Configuration/'));
		$mockPackageFlow->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.Flow'));

		$mockPackages = array(
			'TYPO3.Flow' => $mockPackageFlow
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_POLICY, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedConfiguration = array(
			'roles' => array(
				'TYPO3.Flow:Customer' => array(),
				'TYPO3.Flow:Expert' => array('TYPO3.Flow:Customer')
			),
			'acls' => array(
				'Everybody' => array(),
				'Anonymous' => array(),
				'TYPO3.Flow:Customer' => array(),
				'TYPO3.Flow:Expert' => array()
			)
		);
		$this->assertEquals($expectedConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_POLICY]);
	}

	/**
	 * @expectedException \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
	 * @test
	 */
	public function loadConfigurationThrowsExceptionIfPolicyRedefinesSystemRoles() {
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(
			function($pathAndFilename) {
				if ($pathAndFilename === 'Flow/Configuration/Policy') {
					return array(
						'roles' => array(
							'Customer' => array(),
							'Everybody' => array()
						)
					);
				}
			}
		));

		$mockPackageFlow = $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('Flow/Configuration/'));
		$mockPackageFlow->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.Flow'));

		$mockPackages = array(
			'TYPO3.Flow' => $mockPackageFlow
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_POLICY, $mockPackages);
	}

	/**
	 * @test
	 */
	public function saveConfigurationCacheSavesTheCurrentConfigurationAsPhpCode() {
		vfsStream::setup('Flow');
		mkdir(vfsStream::url('Flow/Configuration'));

		$temporaryDirectoryPath = vfsStream::url('Flow/TemporaryDirectory') . '/';
		$includeCachedConfigurationsPathAndFilename = vfsStream::url('Flow/Configuration/IncludeCachedConfigurations.php');

		$mockConfigurations = array(
			ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches'),
			ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array('settings' => array('foo' => 'bar'))
		);

		$mockEnvironment = $this->getMock('TYPO3\Flow\Utility\Environment', array('getPathToTemporaryDirectory'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->injectEnvironment($mockEnvironment);
		$configurationManager->_set('includeCachedConfigurationsPathAndFilename', $includeCachedConfigurationsPathAndFilename);
		$configurationManager->_set('context', 'FooContext');
		$configurationManager->_set('configurations', $mockConfigurations);

		$configurationManager->_call('saveConfigurationCache');

		$expectedInclusionCode = <<< "EOD"
<?php
if (FLOW_PATH_ROOT !== 'XXX' || !file_exists('vfs://Flow/TemporaryDirectory/Configuration/FooContextConfigurations.php')) {
	unlink(__FILE__);
	return array();
}
return require 'vfs://Flow/TemporaryDirectory/Configuration/FooContextConfigurations.php';
?>
EOD;
		$expectedInclusionCode = str_replace('XXX', FLOW_PATH_ROOT, $expectedInclusionCode);
		$this->assertTrue(file_exists($temporaryDirectoryPath . 'Configuration'));
		$this->assertStringEqualsFile($includeCachedConfigurationsPathAndFilename, $expectedInclusionCode);
		$this->assertFileExists($temporaryDirectoryPath . 'Configuration/FooContextConfigurations.php');
		$this->assertSame($mockConfigurations, require($temporaryDirectoryPath . 'Configuration/FooContextConfigurations.php'));
	}

	/**
	 * @test
	 */
	public function postProcessConfigurationReplacesConstantMarkersByRealGlobalConstants() {
		$settings = array(
			'foo' => 'bar',
			'baz' => '%PHP_VERSION%',
			'inspiring' => array(
				'people' => array(
					'to' => '%FLOW_PATH_ROOT%'
				)
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessConfiguration', $settings);

		$this->assertSame(PHP_VERSION, $settings['baz']);
		$this->assertSame(FLOW_PATH_ROOT, $settings['inspiring']['people']['to']);
	}

	/**
	 * @test
	 */
	public function postProcessConfigurationMaintainsConstantTypeIfOnlyValue() {
		$settings = array(
			'foo' => 'bar',
			'anIntegerConstant' => '%PHP_VERSION_ID%',
			'casted' => array(
				'to' => array(
					'string' => 'Version id is %PHP_VERSION_ID%'
				)
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessConfiguration', $settings);

		$this->assertInternalType('integer', $settings['anIntegerConstant']);
		$this->assertSame(PHP_VERSION_ID, $settings['anIntegerConstant']);

		$this->assertInternalType('string', $settings['casted']['to']['string']);
		$this->assertSame('Version id is ' . PHP_VERSION_ID, $settings['casted']['to']['string']);
	}

	/**
	 * @test
	 */
	public function postProcessConfigurationReplacesClassConstantMarkersWithApproppriateConstants() {
		$settings = array(
			'foo' => 'bar',
			'baz' => '%TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY%',
			'inspiring' => array(
				'people' => array(
					'to' => '%TYPO3\Flow\Core\Bootstrap::MAXIMUM_PHP_VERSION%',
					'share' => '%TYPO3\Flow\Package\PackageInterface::DIRECTORY_CLASSES%'
				)
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessConfiguration', $settings);

		$this->assertSame(ConfigurationManager::CONFIGURATION_TYPE_POLICY, $settings['baz']);
		$this->assertSame(\TYPO3\Flow\Core\Bootstrap::MAXIMUM_PHP_VERSION, $settings['inspiring']['people']['to']);
		$this->assertSame(\TYPO3\Flow\Package\PackageInterface::DIRECTORY_CLASSES, $settings['inspiring']['people']['share']);
	}

	/**
	 * We expect that the context specific routes are loaded *first*
	 *
	 * @test
	 */
	public function loadConfigurationForRoutesLoadsContextSpecificRoutesFirst() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageRoutesCallback', 'Testing/System1');

		$mockPackages = $this->getMockPackages();
		$configurationManager->setPackages($mockPackages);
		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedRoutesConfiguration = array(
			array(
				'name' => 'GlobalSubContextRoute1',
				'uriPattern' => 'globalSubContextRoute1'
			),
			array(
				'name' => 'GlobalSubContextRoute2',
				'uriPattern' => 'globalSubContextRoute2'
			),
			// BEGIN SUBROUTES
			array(
				'name' => 'GlobalContextRoute1 :: PackageSubContextRoute1',
				'uriPattern' => 'globalContextRoute1/packageSubContextRoute1'
			),
			array(
				'name' => 'GlobalContextRoute1 :: PackageSubContextRoute2',
				'uriPattern' => 'globalContextRoute1/packageSubContextRoute2'
			),
			array(
				'name' => 'GlobalContextRoute1 :: PackageContextRoute1',
				'uriPattern' => 'globalContextRoute1/packageContextRoute1'
			),
			array(
				'name' => 'GlobalContextRoute1 :: PackageContextRoute2',
				'uriPattern' => 'globalContextRoute1/packageContextRoute2'
			),
			array(
				'name' => 'GlobalContextRoute1 :: PackageRoute1',
				'uriPattern' => 'globalContextRoute1/packageRoute1'
			),
			array(
				'name' => 'GlobalContextRoute1 :: PackageRoute2',
				'uriPattern' => 'globalContextRoute1/packageRoute2'
			),
			// END SUBROUTES
			array(
				'name' => 'GlobalContextRoute2',
				'uriPattern' => 'globalContextRoute2'
			),
			array(
				'name' => 'GlobalRoute1',
				'uriPattern' => 'globalRoute1'
			),
			array(
				'name' => 'GlobalRoute2',
				'uriPattern' => 'globalRoute2'
			)
		);

		$this->assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
	}

	/**
	 * Callback for the above test.
	 * @param string $filenameAndPath
	 */
	public function packageRoutesCallback($filenameAndPath) {

		// The routes from the innermost context should be added FIRST, such that
		// they take precedence over more generic contexts
		$packageSubContextRoutes = array(
			array(
				'name' => 'PackageSubContextRoute1',
				'uriPattern' => 'packageSubContextRoute1'
			),
			array(
				'name' => 'PackageSubContextRoute2',
				'uriPattern' => 'packageSubContextRoute2'
			),
		);

		$packageContextRoutes = array(
			array(
				'name' => 'PackageContextRoute1',
				'uriPattern' => 'packageContextRoute1'
			),
			array(
				'name' => 'PackageContextRoute2',
				'uriPattern' => 'packageContextRoute2'
			)
		);

		$packageRoutes = array(
			array(
				'name' => 'PackageRoute1',
				'uriPattern' => 'packageRoute1'
			),
			array(
				'name' => 'PackageRoute2',
				'uriPattern' => 'packageRoute2'
			)
		);


		$globalSubContextRoutes = array(
			array(
				'name' => 'GlobalSubContextRoute1',
				'uriPattern' => 'globalSubContextRoute1'
			),
			array(
				'name' => 'GlobalSubContextRoute2',
				'uriPattern' => 'globalSubContextRoute2'
			)
		);

		$globalContextRoutes = array(
			array(
				'name' => 'GlobalContextRoute1',
				'uriPattern' => 'globalContextRoute1/<PackageSubroutes>',
				'subRoutes' => array(
					'PackageSubroutes' => array(
						'package' => 'TYPO3.Flow'
					)
				),
			),
			array(
				'name' => 'GlobalContextRoute2',
				'uriPattern' => 'globalContextRoute2'
			)
		);

		$globalRoutes = array(
			array(
				'name' => 'GlobalRoute1',
				'uriPattern' => 'globalRoute1'
			),
			array(
				'name' => 'GlobalRoute2',
				'uriPattern' => 'globalRoute2'
			)
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Routes' : return $packageRoutes;
			case 'Flow/Configuration/Testing/Routes' : return $packageContextRoutes;
			case 'Flow/Configuration/Testing/System1/Routes' : return $packageSubContextRoutes;
			case FLOW_PATH_CONFIGURATION . 'Routes' : return $globalRoutes;
			case FLOW_PATH_CONFIGURATION . 'Testing/Routes' : return $globalContextRoutes;
			case FLOW_PATH_CONFIGURATION . 'Testing/System1/Routes' : return $globalSubContextRoutes;
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationForRoutesLoadsSubRoutesRecursively() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageSubRoutesCallback', 'Testing/System1');

		$mockPackages = $this->getMockPackages();
		$configurationManager->setPackages($mockPackages);
		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedRoutesConfiguration = array(
			array (
				'name' => 'a :: b1 :: c1',
				'uriPattern' => 'a/b1/c1'
			),
			array (
				'name' => 'a :: b2 :: d1 :: c1',
				'uriPattern' => 'a/b2/d1/c1'
			),
			array (
				'name' => 'a :: b1 :: c2 :: e1',
				'uriPattern' => 'a/b1/c2/e1'
			),
			array (
				'name' => 'a :: b2 :: d1 :: c2 :: e1',
				'uriPattern' => 'a/b2/d1/c2/e1'
			),
			array (
				'name' => 'a :: b1 :: c2 :: e2',
				'uriPattern' => 'a/b1/c2/e2'
			),
			array (
				'name' => 'a :: b2 :: d1 :: c2 :: e2',
				'uriPattern' => 'a/b2/d1/c2/e2'
			),
		);

		$this->assertSame($expectedRoutesConfiguration, $actualConfigurations[ConfigurationManager::CONFIGURATION_TYPE_ROUTES]);
	}

	/**
	 * Callback for the above test.
	 * @param string $filenameAndPath
	 */
	public function packageSubRoutesCallback($filenameAndPath) {

		$globalRoutes = array(
			array(
				'name' => 'a',
				'uriPattern' => 'a/<b>/<c>',
				'subRoutes' => array(
					'b' => array(
						'package' => 'TYPO3.Flow',
						'suffix' => 'b'
					),
					'c' => array(
						'package' => 'TYPO3.Flow',
						'suffix' => 'c'
					)
				)
			)
		);

		$subRoutesB = array(
			array(
				'name' => 'b1',
				'uriPattern' => 'b1'
			),
			array(
				'name' => 'b2',
				'uriPattern' => 'b2/<d>',
				'subRoutes' => array(
					'd' => array(
						'package' => 'TYPO3.Flow',
						'suffix' => 'd'
					)
				)
			)
		);

		$subRoutesC = array(
			array(
				'name' => 'c1',
				'uriPattern' => 'c1'
			),
			array(
				'name' => 'c2',
				'uriPattern' => 'c2/<e>',
				'subRoutes' => array(
					'e' => array(
						'package' => 'TYPO3.Flow',
						'suffix' => 'e'
					)
				)
			)
		);

		$subRoutesD = array(
			array(
				'name' => 'd1',
				'uriPattern' => 'd1'
			)
		);

		$subRoutesE = array(
			array(
				'name' => 'e1',
				'uriPattern' => 'e1'
			),
			array(
				'name' => 'e2',
				'uriPattern' => 'e2'
			),
		);

		switch ($filenameAndPath) {
			case FLOW_PATH_CONFIGURATION . 'Routes' :
				return $globalRoutes;
			case 'Flow/Configuration/Routes.b' :
				return $subRoutesB;
			case 'Flow/Configuration/Routes.c' :
				return $subRoutesC;
			case 'Flow/Configuration/Routes.d' :
				return $subRoutesD;
			case 'Flow/Configuration/Routes.e' :
				return $subRoutesE;
			default:
				return array();
		}
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Configuration\Exception\RecursionException
	 */
	public function loadConfigurationForRoutesThrowsExceptionIfSubRoutesContainCircularReferences() {
		$mockSubRouteConfiguration =
			array(
				'name' => 'SomeRouteOrSubRoute',
				'uriPattern' => '<PackageSubroutes>',
				'subRoutes' => array(
					'PackageSubroutes' => array(
						'package' => 'TYPO3.Flow'
					)
				),
			);
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnValue(array($mockSubRouteConfiguration)));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(new ApplicationContext('Production')));
		$configurationManager->injectConfigurationSource($mockConfigurationSource);

		$mockPackages = $this->getMockPackages();
		$configurationManager->setPackages($mockPackages);
		$configurationManager->_call('loadConfiguration', ConfigurationManager::CONFIGURATION_TYPE_ROUTES, $mockPackages);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Configuration\Exception\ParseErrorException
	 */
	public function mergeRoutesWithSubRoutesThrowsExceptionIfRouteRefersToNonExistingOrInactivePackages() {
		$routesConfiguration = array(
			array(
				'name' => 'Welcome',
				'uriPattern' => '<WelcomeSubroutes>',
				'subRoutes' => array(
					'WelcomeSubroutes' => array(
						'package' => 'Welcome'
					)
				)
			)
		);
		$subRoutesConfiguration = array();

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(new ApplicationContext('Testing')));
		$configurationManager->_callRef('mergeRoutesWithSubRoutes', $routesConfiguration, $subRoutesConfiguration);
	}

	/**
	 * @test
	 */
	public function mergeRoutesWithSubRoutesRespectsSuffixSubRouteOption() {
		$mockRoutesConfiguration = array(
			array(
				'name' => 'SomeRoute',
				'uriPattern' => '<PackageSubroutes>',
				'subRoutes' => array(
					'PackageSubroutes' => array(
						'package' => 'TYPO3.Flow',
						'suffix' => 'Foo'
					)
				),
			)
		);
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->at(0))->method('load')->with('Flow/Configuration/Testing/System1/Routes.Foo')->will($this->returnValue(array()));
		$mockConfigurationSource->expects($this->at(1))->method('load')->with('Flow/Configuration/Testing/Routes.Foo')->will($this->returnValue(array()));
		$mockConfigurationSource->expects($this->at(2))->method('load')->with('Flow/Configuration/Routes.Foo')->will($this->returnValue(array()));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(new ApplicationContext('Testing/System1')));
		$configurationManager->_set('configurationSource', $mockConfigurationSource);

		$mockPackages = $this->getMockPackages();
		$configurationManager->setPackages($mockPackages);
		$configurationManager->_call('mergeRoutesWithSubRoutes', $mockRoutesConfiguration);
	}

	/**
	 * @test
	 */
	public function buildSubrouteConfigurationsCorrectlyMergesRoutes() {
		$routesConfiguration = array(
			array(
				'name' => 'Welcome',
				'uriPattern' => '<WelcomeSubroutes>',
				'defaults' => array(
					'@package' => 'Welcome'
				),
				'subRoutes' => array(
					'WelcomeSubroutes' => array(
						'package' => 'Welcome'
					)
				),
				'routeParts' => array(
					'foo' => array(
						'bar' => 'baz',
						'baz' => 'Xyz'
					)
				),
				'toLowerCase' => TRUE
			)
		);
		$subRoutesConfiguration = array(
			array(
				'name' => 'Standard route',
				'uriPattern' => 'flow/welcome',
				'defaults' => array(
					'@package' => 'OverriddenPackage',
					'@controller' => 'Standard',
					'@action' => 'index'
				)
			),
			array(
				'name' => 'Redirect',
				'uriPattern' => '',
				'defaults' => array(
					'@controller' => 'Standard',
					'@action' => 'redirect'
				),
				'routeParts' => array(
					'foo' => array(
						'bar' => 'overridden',
						'new' => 'ZZZ'
					)
				),
				'toLowerCase' => FALSE,
				'appendExceedingArguments' => TRUE
			)
		);
		$expectedResult = array(
			array(
				'name' => 'Welcome :: Standard route',
				'uriPattern' => 'flow/welcome',
				'defaults' => array(
					'@package' => 'OverriddenPackage',
					'@controller' => 'Standard',
					'@action' => 'index'
				),
				'routeParts' => array(
					'foo' => array(
						'bar' => 'baz',
						'baz' => 'Xyz'
					)
				),
				'toLowerCase' => TRUE,
			),
			array(
				'name' => 'Welcome :: Redirect',
				'uriPattern' => '',
				'defaults' => array(
					'@package' => 'Welcome',
					'@controller' => 'Standard',
					'@action' => 'redirect'
				),
				'routeParts' => array(
					'foo' => array(
						'bar' => 'overridden',
						'baz' => 'Xyz',
						'new' => 'ZZZ'
					)
				),
				'toLowerCase' => FALSE,
				'appendExceedingArguments' => TRUE
			)
		);
		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(new ApplicationContext('Testing')));
		$actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', array());

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildSubrouteConfigurationsRemovesTrailingSlashFromEmptySubrouteUriPatterns() {
		$routesConfiguration = array(
			array(
				'name' => 'Welcome',
				'uriPattern' => 'welcome/<WelcomeSubroutes>',
				'defaults' => array(
					'@package' => 'Welcome'
				),
				'subRoutes' => array(
					'WelcomeSubroutes' => array(
						'package' => 'Welcome'
					)
				),
			)
		);
		$subRoutesConfiguration = array(
			array(
				'name' => 'Standard Route',
				'uriPattern' => 'foo',
				'defaults' => array(
					'@package' => 'OverriddenPackage',
					'@controller' => 'Standard',
					'@action' => 'index'
				)
			),
			array(
				'name' => 'Fallback',
				'uriPattern' => '',
				'defaults' => array(
					'@controller' => 'Standard',
					'@action' => 'redirect'
				),
			)
		);
		$expectedResult = array(
			array(
				'name' => 'Welcome :: Standard Route',
				'uriPattern' => 'welcome/foo',
				'defaults' => array(
					'@package' => 'OverriddenPackage',
					'@controller' => 'Standard',
					'@action' => 'index'
				),
			),
			array(
				'name' => 'Welcome :: Fallback',
				'uriPattern' => 'welcome',
				'defaults' => array(
					'@package' => 'Welcome',
					'@controller' => 'Standard',
					'@action' => 'redirect'
				),
			)
		);
		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('dummy'), array(new ApplicationContext('Testing')));
		$actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes', array());

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * We expect that the context specific Views configurations are loaded *first*
	 *
	 * @test
	 */
	public function loadConfigurationForViewsLoadsAppendsAllConfigurations() {
		$configurationManager = $this->getConfigurationManagerWithFlowPackage('packageViewConfigurationsCallback', 'Testing/System1');
		$configurationManager->registerConfigurationType('Views', ConfigurationManager::CONFIGURATION_PROCESSING_TYPE_APPEND);
		$configurationManager->setPackages($this->getMockPackages());

		$configurationManager->_call('loadConfiguration', 'Views', $this->getMockPackages());

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedRoutesConfiguration = array(
			array(
				'requestFilter' => 'RequestFilterFromPackage',
			),
			array(
				'requestFilter' => 'RequestFilterFromGlobal',
			),
			array(
				'requestFilter' => 'RequestFilterFromPackageContext',
			),
			array(
				'requestFilter' => 'RequestFilterFromGlobalContext',
			),
			array(
				'requestFilter' => 'RequestFilterFromPackageSubContext',
			),
			array(
				'requestFilter' => 'RequestFilterFromGlobalSubContext',
			),
		);

		$this->assertSame($expectedRoutesConfiguration, $actualConfigurations['Views']);
	}


	/**
	 * Callback for the Views test above.
	 *
	 * @param string $filenameAndPath
	 * @throws \Exception
	 * @return array
	 */
	public function packageViewConfigurationsCallback($filenameAndPath) {

		$packageSubContextViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromPackageSubContext',
			),
		);

		$packageContextViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromPackageContext',
			),
		);

		$packageViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromPackage',
			),
		);


		$globalSubContextViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromGlobalSubContext',
			),
		);

		$globalContextViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromGlobalContext',
			),
		);

		$globalViewConfigurations = array(
			array(
				'requestFilter' => 'RequestFilterFromGlobal',
			),
		);

		switch ($filenameAndPath) {
			case 'Flow/Configuration/Views' : return $packageViewConfigurations;
			case 'Flow/Configuration/Testing/Views' : return $packageContextViewConfigurations;
			case 'Flow/Configuration/Testing/System1/Views' : return $packageSubContextViewConfigurations;
			case FLOW_PATH_CONFIGURATION . 'Views' : return $globalViewConfigurations;
			case FLOW_PATH_CONFIGURATION . 'Testing/Views' : return $globalContextViewConfigurations;
			case FLOW_PATH_CONFIGURATION . 'Testing/System1/Views' : return $globalSubContextViewConfigurations;
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}


	/**
	 * @param string $configurationSourceCallbackName
	 * @param string $contextName
	 * @return ConfigurationManager
	 */
	protected function getConfigurationManagerWithFlowPackage($configurationSourceCallbackName, $contextName) {
		$mockConfigurationSource = $this->getMock('TYPO3\Flow\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, $configurationSourceCallbackName)));

		$configurationManager = $this->getAccessibleMock('TYPO3\Flow\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(new ApplicationContext($contextName)));
		$configurationManager->_set('configurationSource', $mockConfigurationSource);

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		return $configurationManager;
	}

	/**
	 * @return array
	 */
	protected function getMockPackages() {
		$mockPackageFlow = $this->getMock('TYPO3\Flow\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('Flow/Configuration/'));
		$mockPackageFlow->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.Flow'));

		$mockPackages = array(
			'TYPO3.Flow' => $mockPackageFlow
		);

		return $mockPackages;
	}


}
