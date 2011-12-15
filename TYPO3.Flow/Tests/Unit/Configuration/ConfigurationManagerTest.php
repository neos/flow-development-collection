<?php
namespace TYPO3\FLOW3\Tests\Unit\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the configuration manager
 *
 */
class ConfigurationManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getConfigurationForSettingsLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
		$configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo');
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeSettingsReturnsRespectiveConfigurationArray() {
		$expectedConfiguration = array('foo' => 'bar');
		$configurations = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(
				'SomePackage' => $expectedConfiguration
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);

		$actualConfiguration = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeSettingsLoadsConfigurationIfNecessary() {
		$packages = array('SomePackage' => $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $packages);

		$configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
	}

	/**
	 * @test
	 */
	public function getConfigurationForTypeObjectLoadsConfiguration() {
		$packages = array('SomePackage' => $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $packages);

		$configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'SomePackage');
	}

	/**
	 * @test
	 */
	public function getConfigurationForRoutesSignalsCachesAndPackageStatesLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('foo' => 'bar'),
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->at(0))->method('loadConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$configurationManager->expects($this->at(1))->method('loadConfiguration')->with(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES);

		$configurationTypes = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS,
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES
		);
		foreach ($configurationTypes as $configurationType) {
			$configurationManager->getConfiguration($configurationType);
		}
	}

	/**
	 * @test
	 */
	public function getConfigurationForRoutesSignalsCachesAndPackageStatesReturnsRespectiveConfigurationArray() {
		$expectedConfigurations = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches')
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
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
	public function loadConfigurationOverridesSettingsByContext() {
		$mockConfigurationSource = $this->getMock('TYPO3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$mockPackageA = $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageA->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageA/Configuration/'));
		$mockPackageA->expects($this->any())->method('getPackageKey')->will($this->returnValue('PackageA'));

		$mockPackages = array(
			'PackageA' => $mockPackageA,
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'foo' => 'D',
			'bar' => 'A'
		);

		$this->assertSame($expectedSettings, $actualConfigurations[\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['PackageA']);
	}

	/**
	 * @test
	 */
	public function loadConfigurationOverridesGlobalSettingsByContext() {
		$mockConfigurationSource = $this->getMock('TYPO3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$mockPackageFlow3 = $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow3->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('FLOW3/Configuration/'));
		$mockPackageFlow3->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.FLOW3'));

		$mockPackages = array(
			'TYPO3.FLOW3' => $mockPackageFlow3
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'TYPO3' => array(
				'FLOW3' => array(
					'foo' => 'quux',
					'core' => array('context' => 'Testing'),
				)
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
	}

	/**
	 * Callback for the above test.
	 *
	 */
	public function packageSettingsCallback() {
		$filenameAndPath = func_get_arg(0);

		$settingsFlow3 = array(
			'TYPO3' => array(
				'FLOW3' => array()
			)
		);

		$settingsFlow3Testing = array(
			'TYPO3' => array(
				'FLOW3' => array(
					'foo' => 'quux'
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
				'FLOW3' => array(
					'foo' => 'bar'
				)
			)
		);

		switch ($filenameAndPath) {
			case 'FLOW3/Configuration/Settings' : return $settingsFlow3;
			case 'FLOW3/Configuration/SomeContext/Settings' : return array();
			case 'FLOW3/Configuration/Testing/Settings' : return $settingsFlow3Testing;

			case 'PackageA/Configuration/Settings' : return $settingsA;
			case 'PackageA/Configuration/SomeContext/Settings' : return array();
			case 'PackageA/Configuration/Testing/Settings' : return $settingsATesting;
			case 'PackageB/Configuration/Settings' : return $settingsB;
			case 'PackageB/Configuration/SomeContext/Settings' : return array();
			case 'PackageB/Configuration/Testing/Settings' : return array();
			case 'PackageC/Configuration/Settings' : return $settingsC;
			case 'PackageC/Configuration/SomeContext/Settings' : return array();
			case 'PackageC/Configuration/Testing/Settings' : return array();

			case FLOW3_PATH_CONFIGURATION . 'Settings' : return $globalSettings;
			case FLOW3_PATH_CONFIGURATION . 'SomeContext/Settings' : return array();
			case FLOW3_PATH_CONFIGURATION . 'Testing/Settings' : return array();
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationForObjectsOverridesConfigurationByContext() {
		$mockConfigurationSource = $this->getMock('TYPO3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageObjectsCallback')));

		$mockPackageFlow3 = $this->getMock('TYPO3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow3->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('FLOW3/Configuration/'));
		$mockPackageFlow3->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.FLOW3'));

		$mockPackages = array(
			'TYPO3.FLOW3' => $mockPackageFlow3
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'TYPO3.FLOW3' => array(
				'TYPO3\FLOW3\SomeClass' => array(
					'className' => 'Bar'
				)
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS]);
	}

	/**
	 * Callback for the above test.
	 *
	 */
	public function packageObjectsCallback() {
		$filenameAndPath = func_get_arg(0);

		$packageObjects = array(
			'TYPO3\FLOW3\SomeClass' => array(
				'className' => 'Foo'
			)
		);

		$contextObjects = array(
			'TYPO3\FLOW3\SomeClass' => array(
				'className' => 'Bar'
			)
		);

		switch ($filenameAndPath) {
			case 'FLOW3/Configuration/Objects' : return $packageObjects;
			case 'FLOW3/Configuration/Testing/Objects' : return $contextObjects;
			case FLOW3_PATH_CONFIGURATION . 'Objects' : return array();
			case FLOW3_PATH_CONFIGURATION . 'Testing/Objects' : return array();
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 */
	public function loadConfigurationCacheLoadsConfigurationsFromCacheIfACacheFileExists() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('FLOW3'));

		$configurationsCode = <<< "EOD"
<?php
return array('bar' => 'touched');
?>
EOD;

		$includeCachedConfigurationsPathAndFilename = \vfsStream::url('FLOW3/IncludeCachedConfigurations.php');
		file_put_contents($includeCachedConfigurationsPathAndFilename, $configurationsCode);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);

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
		$mockConfigurationSource = $this->getMock('TYPO3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->_call('loadConfiguration', \TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array());
	}

	/**
	 * @test
	 */
	public function saveConfigurationCacheSavesTheCurrentConfigurationAsPhpCode() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('FLOW3'));
		mkdir(\vfsStream::url('FLOW3/Configuration'));

		$temporaryDirectoryPath = \vfsStream::url('FLOW3/TemporaryDirectory') . '/';
		$includeCachedConfigurationsPathAndFilename = \vfsStream::url('FLOW3/Configuration/IncludeCachedConfigurations.php');

		$mockConfigurations = array(
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches'),
			\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array('settings' => array('foo' => 'bar'))
		);

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array('getPathToTemporaryDirectory'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->injectEnvironment($mockEnvironment);
		$configurationManager->_set('includeCachedConfigurationsPathAndFilename', $includeCachedConfigurationsPathAndFilename);
		$configurationManager->_set('context', 'FooContext');
		$configurationManager->_set('configurations', $mockConfigurations);

		$configurationManager->_call('saveConfigurationCache');

		$expectedInclusionCode = <<< "EOD"
<?php
if (FLOW3_PATH_ROOT !== 'XXX' || !file_exists('vfs://FLOW3/TemporaryDirectory/Configuration/FooContextConfigurations.php')) {
	unlink(__FILE__);
	return array();
}
return require 'vfs://FLOW3/TemporaryDirectory/Configuration/FooContextConfigurations.php';
?>
EOD;
		$expectedInclusionCode = str_replace('XXX', FLOW3_PATH_ROOT, $expectedInclusionCode);
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
					'to' => '%FLOW3_PATH_ROOT%'
				)
			)
		);

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessConfiguration', $settings);

		$this->assertSame(PHP_VERSION, $settings['baz']);
		$this->assertSame(FLOW3_PATH_ROOT, $settings['inspiring']['people']['to']);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Configuration\Exception\ParseErrorException
	 */
	public function mergeRoutesWithSubRoutesThrowsExceptionIfRouteRefersToNonExistingOrInactivePackages() {
		$routesConfiguration= array(
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
		$subRoutesConfiguration= array();

		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array('Testing'));
		$configurationManager->_callRef('mergeRoutesWithSubRoutes', $routesConfiguration, $subRoutesConfiguration);
	}

	/**
	 * @test
	 */
	public function buildSubrouteConfigurationsCorrectlyMergesRoutes() {
		$routesConfiguration= array(
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
		$subRoutesConfiguration= array(
			array(
				'name' => 'Standard route',
				'uriPattern' => 'flow3/welcome',
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
				'uriPattern' => 'flow3/welcome',
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
		$configurationManager = $this->getAccessibleMock('TYPO3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array('Testing'));
		$actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes');

		$this->assertEquals($expectedResult, $actualResult);
	}

}
?>