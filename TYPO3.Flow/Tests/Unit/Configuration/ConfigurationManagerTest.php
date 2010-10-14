<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration;

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
class ConfigurationManagerTest extends \F3\Testing\BaseTestCase {

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

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface');
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

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface');

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
	public function shutdownOmmitsSavingTheConfigurationCacheIfNotNeccessary() {
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadConfigurationLoadsSettingsForFLOW3OnlyWhenTheGivenPackagesContainOnlyTheFLOW3Package() {
		$mockPackage = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface', array('load', 'save'));
		$mockConfigurationSource->expects($this->exactly(7))->method('load')->will(
			$this->onConsecutiveCalls(
				array('FLOW3' => array('foo' => 'bar', 'x' => 'y')),	// Packages/Framework/FLOW3/Configuration/Settings.yaml
				array('FLOW3' => array('x1' => 'y1')),						// Packages/Framework/FLOW3/Configuration/FooContext/Settings.yaml
				array('FLOW3' => array('baz' => 'quux')),					// Configuration/Settings.yaml
				array('FLOW3' => array('foo' => 'flow')),					// Configuration/FooContext/Settings.yaml
				array('Foo' => array('aaa' => 'bbb')),						// Packages/.../Foo/Configuration/Settings.yaml
				array('Foo' => array('ccc' => 'ddd')),						// Configuration/Settings.yaml
				array('Foo' => array())											// Configuration/FooContext/Settings.yaml
			)
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->exactly(2))->method('postProcessConfiguration');

		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'FooContext');

		$expectedSettings = array(
			'foo' => 'flow',
			'x' => 'y',
			'x1' => 'y1',
			'baz' => 'quux',
			'core' => array(
				'context' => 'FooContext'
			)
 		);

 		$configurationManager->setPackages(array('FLOW3' => $mockPackage));
		$actualSettings = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');
		$this->assertSame($expectedSettings, $actualSettings);
		$configurationManager->_set('configurations', array('Settings' => array()));

 		$configurationManager->setPackages(array('Foo' => $mockPackage, 'FLOW3' => $mockPackage));
		$actualSettings = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');
		$this->assertSame(array(), $actualSettings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadConfigurationLoadsSettingsForGivenPackagesExceptFLOW3() {
		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface', array('load', 'save'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$mockPackageA = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageA->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageA/Configuration/'));
		$mockPackageB = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageB->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageB/Configuration/'));
		$mockPackageC = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackageC->expects($this->any())->method('getConfigurationPath')->will($this->returnValue('PackageC/Configuration/'));
		$mockPackageFLOW3 = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);

		$mockPackages = array(
			'PackageA' => $mockPackageA,
			'PackageB' => $mockPackageB,
			'FLOW3' => $mockPackageFLOW3,
			'PackageC' => $mockPackageC
		);

		$configurationManager = $this->getAccessibleMock('F3\FLOW3\Configuration\ConfigurationManager', array('postProcessConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'SomeContext');

		$configurationManager->expects($this->once())->method('postProcessConfiguration');

		$configurationManager->_call('loadConfiguration', \F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $mockPackages);

		$actualConfigurations = $configurationManager->_get('configurations');
		$expectedSettings = array(
			'PackageA' => array(
				'foo' => 'A',
				'bar' => 'C'
			),
			'PackageB' => array(
				'foo' => 'B',
				'bar' => 'B'
			),
			'PackageC' => array(
				'baz' => 'C'
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

		switch ($filenameAndPath) {
			case 'PackageA/Configuration/Settings' : return $settingsA;
			case 'PackageB/Configuration/Settings' : return $settingsB;
			case 'PackageC/Configuration/Settings' : return $settingsC;
			case FLOW3_PATH_CONFIGURATION . 'Settings' : return array();
			case FLOW3_PATH_CONFIGURATION . 'SomeContext/Settings' : return array();
			default:
				throw new Exception('Unexpected filename: ' . $filenameAndPath);
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationCacheSavesTheCurrentConfigurationAsPHPCode() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('FLOW3'));
		mkdir (\vfsStream::url('FLOW3/Configuration'));

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

}
?>