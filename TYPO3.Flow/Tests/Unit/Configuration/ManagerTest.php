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

require_once('vfs/vfsStream.php');

/**
 * Testcase for the configuration manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructLoadsPotentiallyExistingCachedConfiguration() {
		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadConfigurationCache'), array(), '', FALSE);
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
		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadSettings'), array(), '', FALSE);
		$configurationManager->expects($this->never())->method('loadSettings');

		$configurations = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS => array(
				'SomePackage' => 'foo'
			)
		);

		$configurationManager->_set('configurations', $configurations);
		$actualSettings = $configurationManager->getSettings('SomePackage');
		$this->assertSame($configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS]['SomePackage'], $actualSettings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsLoadsSettingsIfTheyHaventBeenAlready() {
		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadSettings'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('loadSettings');

		$configurationManager->setPackages(array());
		$configurationManager->_set('configurations', array());
		$configurationManager->getSettings('SomePackage');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Configuration\Exception\InvalidConfigurationType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationOnlySupportsSpecialConfigurationTypes() {
		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForTypePackageOrObjectReturnsRespectiveConfigurationArray() {
		$expectedConfiguration = array('foo' => 'bar');
		$configurations = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE => array(
				'SomePackage' => $expectedConfiguration
			)
		);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);

		$actualConfiguration = $configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE, 'SomePackage');
		$this->assertSame($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForTypePackageOrObjectLoadsConfigurationIfNecessary() {
		$packages = array('SomePackage' => $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE));

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', array(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE => array()));
		$configurationManager->setPackages($packages);
		$configurationManager->expects($this->once())->method('loadConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE, $packages);

		$configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE, 'SomePackage');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationForRoutesSignalsCachesAndPackageStatesLoadsConfigurationIfNecessary() {
		$initialConfigurations = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('foo' => 'bar'),
		);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadConfiguration'), array(), '', FALSE);
		$configurationManager->_set('configurations', $initialConfigurations);

		$configurationManager->expects($this->at(0))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES);
		$configurationManager->expects($this->at(1))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES);
		$configurationManager->expects($this->at(2))->method('loadConfiguration')->with(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES);

		$configurationTypes = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES
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
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES => array('caches'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES => array('packagestates')
		);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('loadConfiguration'), array(), '', FALSE);
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

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->setConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES, $expectedPackageStates);

		$actualConfiguration = $configurationManager->_get('configurations');
		$this->assertSame($expectedPackageStates, $actualConfiguration[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES]);
		$this->assertTRUE($configurationManager->_get('cacheNeedsUpdate'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfigurationThrowsExceptionOnUnsupportedConfigurationType() {
		$unsupportedConfigurationTypes = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_FLOW3,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SECURITY,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS
		);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		foreach ($unsupportedConfigurationTypes as $configurationType) {
			try {
				$configurationManager->setConfiguration($configurationType, array());
				$this->fail('Did not throw exception for configuration type ' . $configurationType);
			} catch (\F3\FLOW3\Configuration\Exception\InvalidConfigurationType $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationAllowsForSavingBackPackageStates() {
		$configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES]['Foo']['active'] = TRUE;

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface');
		$mockConfigurationSource->expects($this->once())->method('save')->with(FLOW3_PATH_CONFIGURATION . \F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES, $configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES]);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->injectConfigurationSource($mockConfigurationSource);

		$configurationManager->saveConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function saveConfigurationThrowsExceptionOnUnsupportedConfigurationType() {
		$unsupportedConfigurationTypes = array(
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_FLOW3,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SECURITY,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS,
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS
		);

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface');

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->injectConfigurationSource($mockConfigurationSource);

		foreach ($unsupportedConfigurationTypes as $configurationType) {
			try {
				$configurationManager->saveConfiguration($configurationType);
				$this->fail('Did not throw exception for configuration type ' . $configurationType);
			} catch (\F3\FLOW3\Configuration\Exception\InvalidConfigurationType $exception) {
			}
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownSavesConfigurationCacheIfNeccessary() {
		$configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = TRUE;

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('saveConfigurationCache'), array(), '', FALSE);
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
		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('saveConfigurationCache'), array(), '', FALSE);
		$configurationManager->expects($this->never())->method('saveConfigurationCache');

		$configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = FALSE;
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->_set('cacheNeedsUpdate', TRUE);
		$configurationManager->shutdown();

		$configurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] = TRUE;
		$configurationManager->_set('configurations', $configurations);
		$configurationManager->_set('cacheNeedsUpdate', FALSE);
		$configurationManager->shutdown();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadSettingsLoadsSettingsForFLOW3OnlyWhenTheGivenPackagesContainOnlyTheFLOW3Package() {
		$mockFLOW3Package = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);

		$mockConfigurationSource = $this->getMock('F3\FLOW3\Configuration\Source\SourceInterface', array('load', 'save'));
		$mockConfigurationSource->expects($this->exactly(3))->method('load')->will($this->onConsecutiveCalls(array('foo' => 'bar'), array('baz' => 'quux'), array('foo' => 'flow')));

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('postProcessSettings'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('postProcessSettings');

		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'FooContext');

		$configurationManager->_call('loadSettings', array('FLOW3' => $mockFLOW3Package));

		$expectedSettings = array(
			'foo' => 'flow',
			'baz' => 'quux',
			'core' => array(
				'context' => 'FooContext'
			)
 		);
		$actualSettings = $configurationManager->getSettings('FLOW3');

		$this->assertSame($expectedSettings, $actualSettings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadSettingsLoadsSettingsForGivenPackagesExceptFLOW3() {
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

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('postProcessSettings'), array(), '', FALSE);
		$configurationManager->_set('configurationSource', $mockConfigurationSource);
		$configurationManager->_set('context', 'Testing');

		$configurationManager->expects($this->once())->method('postProcessSettings');

		$configurationManager->_call('loadSettings', $mockPackages);

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

		$this->assertSame($expectedSettings, $actualConfigurations[\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS]);
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
			case FLOW3_PATH_CONFIGURATION . 'Testing/Settings' : return array();
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

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('postProcessSettings'), array(), '', FALSE);

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
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES => array('routes'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS => array('signalsslots'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES => array('caches'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGESTATES => array('packagestates'),
			\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SETTINGS => array('settings' => array('foo' => 'bar'))
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array('getPathToTemporaryDirectory'), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getPathToTemporaryDirectory')->will($this->returnValue($temporaryDirectoryPath));

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('postProcessSettings'), array(), '', FALSE);
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
	public function postProcessSettingsReplacesConstantMarkersByRealGlobalConstants() {
		$settings = array(
			'foo' => 'bar',
			'baz' => '%PHP_VERSION%',
			'inspiring' => array(
				'people' => array(
					'to' => '%FLOW3_PATH_ROOT%'
				)
			)
		);

		$configurationManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager'), array('dummy'), array(), '', FALSE);
		$configurationManager->_callRef('postProcessSettings', $settings);

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

		$managerClassName = $this->buildAccessibleProxy('F3\FLOW3\Configuration\Manager');
		$manager = new $managerClassName('Testing', array());
		$manager->_callRef('mergeRoutesWithSubRoutes', $routesConfiguration, $subRoutesConfiguration);

		$this->assertEquals(0, count($routesConfiguration));
	}
}
?>