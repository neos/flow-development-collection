<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the configuration manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConfigurationManagerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructLoadsPotentiallyExistingCachedConfiguration() {
		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('loadConfigurationCache'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('loadConfigurationCache');

		$configurationManager->__construct('Testing');
		$expectedFilename = FLOW3_PATH_CONFIGURATION . 'Testing/IncludeCachedConfigurations.php';
		$actualFilename = $configurationManager->_get('includeCachedConfigurationsPathAndFilename');
		$this->assertSame($expectedFilename, $actualFilename);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsReturnsSettingsOfTheGivenPackage() {
		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage')
			->will($this->returnValue(array('foo' => 'bar')));

		$actualSettings = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'SomePackage');
		$this->assertSame(array('foo' => 'bar'), $actualSettings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForSettingsLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array(),
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
		$configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Foo');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForTypePackageOrObjectReturnsRespectiveConfigurationArray() {
		$expectedConfiguration = array('foo' => 'bar');
		$configurations = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE => array(
				'SomePackage' => $expectedConfiguration
			)
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);

		$actualConfiguration = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE, 'SomePackage');
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForTypePackageOrObjectLoadsConfigurationIfNecessary() {
		$packages = array('SomePackage' => $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE, $packages);

		$configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE, 'SomePackage');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForRoutesSignalsCachesAndPackageStatesLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('foo' => 'bar'),
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->at(0))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
		$configurationManager->expects($this->at(1))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES);
		$configurationManager->expects($this->at(2))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES);

		$configurationTypes = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES
		);
		foreach ($configurationTypes as $configurationType) {
			$configurationManager->getConfiguration($configurationType);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForRoutesSignalsCachesAndPackageStatesReturnsRespectiveConfigurationArray() {
		$expectedConfigurations = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES => array('packagestates')
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $expectedConfigurations);
		$configurationManager->expects($this->never())->method('loadConfiguration');

		foreach ($expectedConfigurations as $configurationType => $expectedConfiguration) {
			$actualConfiguration = $configurationManager->getConfiguration($configurationType);
			$this->assertSame($expectedConfiguration, $actualConfiguration);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfigurationAllowsForUpdatingPackageStates() {
		$expectedPackageStates = array('Foo' => array('active' => TRUE));

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->setConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES, $expectedPackageStates);

		$actualConfiguration = $configurationManager->_get('configurations');
		$this->assertSame($expectedPackageStates, $actualConfiguration[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES]);
		$this->assertTRUE($configurationManager->_get('cacheNeedsUpdate'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfigurationThrowsExceptionOnUnsupportedConfigurationType() {
		$unsupportedConfigurationTypes = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FLOW3,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		foreach ($unsupportedConfigurationTypes as $configurationType) {
			try {
				$configurationManager->setConfiguration($configurationType, array());
				$this->fail('Did not throw exception for configuration type ' . $configurationType);
			} catch (\F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationAllowsForSavingBackPackageStates() {
		$configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES]['Foo']['active'] = TRUE;

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\YamlSource');
		$mockConfigurationSource->expects($this->once())->method('save')->with(FLOW3_PATH_CONFIGURATION . \F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES, $configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES]);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->injectConfigurationSource($mockConfigurationSource);

		$configurationManager->saveConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationThrowsExceptionOnUnsupportedConfigurationType() {
		$unsupportedConfigurationTypes = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_FLOW3,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_POLICY,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS
		);

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\YamlSource');

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->injectConfigurationSource($mockConfigurationSource);

		foreach ($unsupportedConfigurationTypes as $configurationType) {
			try {
				$configurationManager->saveConfiguration($configurationType);
				$this->fail('Did not throw exception for configuration type ' . $configurationType);
			} catch (\F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownSavesConfigurationCacheIfNeccessary() {
		$configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = TRUE;

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('saveConfigurationCache'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('saveConfigurationCache');
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->_set('cacheNeedsUpdate', TRUE);

		$configurationManager->shutdown();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownOmitsSavingTheConfigurationCacheIfNotNeccessary() {
		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('saveConfigurationCache'), array(), '', FALSE);
		$configurationManager->expects($this->never())->method('saveConfigurationCache');

		$configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = FALSE;
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->_set('cacheNeedsUpdate', TRUE);
		$configurationManager->shutdown();

		$configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = TRUE;
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->_set('cacheNeedsUpdate', FALSE);
		$configurationManager->shutdown();
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function loadConfigurationOverridesSettingsByContext() {
		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$mockPackageA = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageA->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageA/Configuration/'));
		$mockPackageA->expects($this->any())->method('getPackageKey')->will($this->returnValue('PackageA'));
		$mockPackageFlow3 = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow3->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('FLOW3/Configuration/'));
		$mockPackageFlow3->expects($this->any())->method('getPackageKey')->will($this->returnValue('FLOW3'));

		$mockPackages = array(
			'PackageA' => $mockPackageA,
			'FLOW3' => $mockPackageFlow3
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'FLOW3' => array('core' => array('context' => 'Testing')),
			'PackageA' => array(
				'foo' => 'D',
				'bar' => 'A'
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]);
	}

	/**
	 * Callback for the above test.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function packageSettingsCallback() {
		$filenameAndPath = func_get_arg(0);

		$settingsFlow3 = array(
			'FLOW3' => array(
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

		switch ($filenameAndPath) {
			case 'FLOW3/Configuration/Settings' : return $settingsFlow3;
			case 'FLOW3/Configuration/SomeContext/Settings' : return array();
			case 'FLOW3/Configuration/Testing/Settings' : return array();

			case 'PackageA/Configuration/Settings' : return $settingsA;
			case 'PackageA/Configuration/SomeContext/Settings' : return array();
			case 'PackageA/Configuration/Testing/Settings' : return $settingsATesting;
			case 'PackageB/Configuration/Settings' : return $settingsB;
			case 'PackageB/Configuration/SomeContext/Settings' : return array();
			case 'PackageB/Configuration/Testing/Settings' : return array();
			case 'PackageC/Configuration/Settings' : return $settingsC;
			case 'PackageC/Configuration/SomeContext/Settings' : return array();
			case 'PackageC/Configuration/Testing/Settings' : return array();

			case FLOW3_PATH_CONFIGURATION . 'Settings' : return array();
			case FLOW3_PATH_CONFIGURATION . 'SomeContext/Settings' : return array();
			case FLOW3_PATH_CONFIGURATION . 'Testing/Settings' : return array();
			default:
				throw new \Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function loadConfigurationForObjectsOverridesConfigurationByContext() {
		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageObjectsCallback')));

		$mockPackageFlow3 = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageFlow3->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('FLOW3/Configuration/'));
		$mockPackageFlow3->expects($this->any())->method('getPackageKey')->will($this->returnValue('FLOW3'));

		$mockPackages = array(
			'FLOW3' => $mockPackageFlow3
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'FLOW3' => array(
				'F3\FLOW3\SomeClass' => array(
					'className' => 'Bar'
				)
			)
		);

		$this->assertSame($expectedSettings, $actualConfigurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS]);
	}

	/**
	 * Callback for the above test.
	 *
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function packageObjectsCallback() {
		$filenameAndPath = func_get_arg(0);

		$packageObjects = array(
			'F3\FLOW3\SomeClass' => array(
				'className' => 'Foo'
			)
		);

		$contextObjects = array(
			'F3\FLOW3\SomeClass' => array(
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
	 * @author Robert Lemke <robert@typo3.org>
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

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);

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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function loadConfigurationCorrectlyMergesSettings() {
		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\YamlSource', array('load', 'save'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->_set('configurations', array());
		$configurationManager->_call('loadConfiguration', \F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationCacheSavesTheCurrentConfigurationAsPhpCode() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('FLOW3'));
		mkdir(\vfsStream::url('FLOW3/Configuration'));

		$temporaryDirectoryPath = \vfsStream::url('FLOW3/TemporaryDirectory') . '/';
		$includeCachedConfigurationsPathAndFilename = \vfsStream::url('FLOW3/Configuration/IncludeCachedConfigurations.php');

		$mockConfigurations = array(
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES => array('caches'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES => array('packagestates'),
			\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS => array('settings' => array('foo' => 'bar'))
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array('getPathToTemporaryDirectory'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->injectEnvironment($mockEnvironment);
		$configurationManager->_set('includeCachedConfigurationsPathAndFilename', $includeCachedConfigurationsPathAndFilename);
		$configurationManager->_set('context', 'FooContext');
		$configurationManager->_set('configurations', $mockConfigurations);

		$configurationManager->_call('saveConfigurationCache');

		$expectedInclusionCode = <<< "EOD"
<?php
if (file_exists('vfs://FLOW3/TemporaryDirectory/Configuration/FooContextConfigurations.php')) {
	return require 'vfs://FLOW3/TemporaryDirectory/Configuration/FooContextConfigurations.php';
} else {
	unlink(__FILE__);
	return array();
}
?>
EOD;
		$this->assertTrue(file_exists($temporaryDirectoryPath . 'Configuration'));
		$this->assertStringEqualsFile($includeCachedConfigurationsPathAndFilename, $expectedInclusionCode);
		$this->assertFileExists($temporaryDirectoryPath . 'Configuration/FooContextConfigurations.php');
		$this->assertSame($mockConfigurations, require($temporaryDirectoryPath . 'Configuration/FooContextConfigurations.php'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
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

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessConfiguration', $settings);

		$this->assertSame(PHP_VERSION, $settings['baz']);
		$this->assertSame(FLOW3_PATH_ROOT, $settings['inspiring']['people']['to']);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mergeRoutesWithSubRoutesSkipsInactivePackages() {
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

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array('Testing'));
		$configurationManager->_callRef('mergeRoutesWithSubRoutes', $routesConfiguration, $subRoutesConfiguration);

		$this->assertEquals(0, count($routesConfiguration));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
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
				'toLowerCase' => FALSE
			)
		);
		$expectedResult = array(
			array(
				'name' => 'Welcome :: Standard route',
				'uriPattern' => 'flow3/welcome',
				'defaults' => array(
					'@package' => 'Welcome',
					'@controller' => 'Standard',
					'@action' => 'index'
				),
				'routeParts' => array(
					'foo' => array(
						'bar' => 'baz',
						'baz' => 'Xyz'
					)
				),
				'toLowerCase' => TRUE
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
				'toLowerCase' => FALSE
			)
		);
		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('dummy'), array('Testing'));
		$actualResult = $configurationManager->_call('buildSubrouteConfigurations', $routesConfiguration, $subRoutesConfiguration, 'WelcomeSubroutes');

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getSettingsWithConfigurationPathReturnsPartialSettings() {
		$configurations[\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration'] = array('foo' => 'bar');

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('saveConfigurationCache'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);

		$result = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, NULL, array('FLOW3', 'configuration'));
		$this->assertEquals(array('foo' => 'bar'), $result);
	}

}
?>