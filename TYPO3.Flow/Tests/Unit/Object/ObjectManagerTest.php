<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Object;

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
 * Testcase for the Object Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectManagerTest extends \F3\Testing\BaseTestCase {

	protected $mockStaticObjectContainerClassName;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Base'));

		$this->mockStaticObjectContainerClassName = get_class($this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeUsesAnExistingStaticObjectContainerIfClassDetectionIsDisabled() {
			// Emulate the temporary directory rendering of Environment because we can't mock it in this case:
		$temporaryDirectoryBase = 'vfs://Base/Temporary/';
		$processUser = extension_loaded('posix') ? posix_getpwuid(posix_geteuid()) : array('name' => 'default');
		$pathHash = substr(md5(FLOW3_PATH_WEB . PHP_SAPI . $processUser['name'] . 'Development'), 0, 12);
		$temporaryDirectory = $temporaryDirectoryBase . $pathHash . '/';
		\F3\FLOW3\Utility\Files::createDirectoryRecursively($temporaryDirectory);

		$id = uniqid('staticObjectContainerInclusionProval');
		$staticObjectContainerInclusionProvalCode = "
			<?php
				class $id {}
			?>
		";
		file_put_contents($temporaryDirectory . 'StaticObjectContainer.php', $staticObjectContainerInclusionProvalCode);

		$settings = array();
		$settings['FLOW3']['monitor']['detectClassChanges'] = FALSE;
		$settings['FLOW3']['utility']['environment']['temporaryDirectoryBase'] = 'vfs://Base/Temporary/';

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3')->will($this->returnValue($settings['FLOW3']));
		$mockConfigurationManager->expects($this->at(1))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->will($this->returnValue($settings));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->injectConfigurationManager($mockConfigurationManager);
		$objectManager->injectClassLoader($this->getMock('F3\FLOW3\Resource\ClassLoader', array(), array(), '', FALSE));

		$_FILES = array(); // avoid error in Environment->initializeObject()
		$objectManager->initialize();

		$this->assertTrue(class_exists($id, FALSE));
		$this->assertType($this->mockStaticObjectContainerClassName, $objectManager->_get('objectContainer'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeCreatesAnIntermediateDynamicObjectContainerIfNoStaticObjectContainerExistsOrClassDetectionIsEnabled() {
		$settings = array();
		$settings['FLOW3']['monitor']['detectClassChanges'] = FALSE;
		$settings['FLOW3']['utility']['environment']['temporaryDirectoryBase'] = 'vfs://Base/Temporary/';

		$rawObjectConfigurations = array(
			'F3\FLOW3\Object\ObjectManagerInterface' => array(
				'scope' => 'singleton',
				'className' => 'F3\FLOW3\Object\ObjectManager'
			),
			'F3\FLOW3\Resource\ClassLoader' => array(),
			'F3\FLOW3\Utility\Environment' => array(),
			'F3\FLOW3\Configuration\ConfigurationManager' => array()
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3')->will($this->returnValue($settings['FLOW3']));
		$mockConfigurationManager->expects($this->at(1))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'FLOW3')->will($this->returnValue($rawObjectConfigurations));
		$mockConfigurationManager->expects($this->at(2))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->will($this->returnValue($settings));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$_FILES = array(); // avoid error in Environment->initializeObject()
		$objectManager->initialize();

		$this->assertType('F3\FLOW3\Object\Container\DynamicObjectContainer', $objectManager->_get('objectContainer'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectContainerJustLoadsTheProxyClassesIfTheCurrentContainerIsAlreadyStatic() {
		mkdir('vfs://Base/Temporary');
		file_put_contents('vfs://Base/Temporary/StaticObjectContainer.php', 'x');
		$mockActivePackages = array('FLOW3' => 'PackageObject', 'Foo' => 'PackageObject');
		$mockSettings = array('Foo' => 'Bar');

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->will($this->returnValue($mockSettings));

		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('injectSettings')->with($mockSettings);

		$mockAopFramework = $this->getMock('F3\FLOW3\AOP\Framework', array(), array(), '', FALSE);
		$mockAopFramework->expects($this->once())->method('loadProxyClasses');
		$mockAopFramework->expects($this->never())->method('initialize');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('get'));
		$objectManager->expects($this->once())->method('get')->with('F3\FLOW3\AOP\Framework')->will($this->returnValue($mockAopFramework));
		$objectManager->injectConfigurationManager($mockConfigurationManager);
		$objectManager->_set('staticObjectContainerClassName', get_class($mockObjectContainer));
		$objectManager->_set('staticObjectContainerPathAndFilename', 'vfs://Base/Temporary/StaticObjectContainer.php');
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$objectManager->initializeObjectContainer($mockActivePackages);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectContainerBuildsAndUsesAStaticObjectContainerIfNoneExists() {
		mkdir('vfs://Base/Temporary');
		$staticObjectContainerClassName = uniqid('staticObjectContainerInclusionProval');
		$staticObjectContainerCode = "
			<?php
				class $staticObjectContainerClassName {
					public function import() {}
					public function injectSettings() {}
				}
			?>
		";

		$mockActivePackages = array('FLOW3' => 'PackageObject', 'Foo' => 'PackageObject');
		$mockSettings = array('Foo' => 'Bar');
		$mockObjectConfigurations = array('ObjectConfigurations');

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->will($this->returnValue($mockSettings));

		$mockDynamicObjectContainer = $this->getMock('F3\FLOW3\Object\Container\DynamicObjectContainer', array(), array(), '', FALSE);

		$mockObjectContainerBuilder = $this->getMock('F3\FLOW3\Object\Container\ObjectContainerBuilder', array(), array(), '', FALSE);
		$mockObjectContainerBuilder->expects($this->once())->method('buildObjectContainer')->with($mockObjectConfigurations)->will($this->returnValue($staticObjectContainerCode));

		$mockAopFramework = $this->getMock('F3\FLOW3\AOP\Framework', array(), array(), '', FALSE);
		$mockAopFramework->expects($this->once())->method('initialize')->with($mockObjectConfigurations);

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('get', 'buildPackageObjectConfigurations'));
		$objectManager->expects($this->at(0))->method('buildPackageObjectConfigurations')->with($mockActivePackages)->will($this->returnValue($mockObjectConfigurations));
		$objectManager->expects($this->at(1))->method('get')->with('F3\FLOW3\AOP\Framework')->will($this->returnValue($mockAopFramework));
		$objectManager->expects($this->at(2))->method('get')->with('F3\FLOW3\Object\Container\ObjectContainerBuilder')->will($this->returnValue($mockObjectContainerBuilder));

		$objectManager->injectConfigurationManager($mockConfigurationManager);
		$objectManager->_set('staticObjectContainerClassName', $staticObjectContainerClassName);
		$objectManager->_set('staticObjectContainerPathAndFilename', 'vfs://Base/Temporary/StaticObjectContainer.php');
		$objectManager->_set('objectContainer', $mockDynamicObjectContainer);

		$objectManager->initializeObjectContainer($mockActivePackages);

		$this->assertType($staticObjectContainerClassName, $objectManager->_get('objectContainer'));
		$this->assertTrue(class_exists($staticObjectContainerClassName, FALSE));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectContainerSwitchesFromDynamicToStaticContainerIfOneAlreadyExists() {
		mkdir('vfs://Base/Temporary');
		$id = uniqid('staticObjectContainerInclusionProval');
		$staticObjectContainerCode = "
			<?php
				define('$id', TRUE);
			?>
		";
		file_put_contents('vfs://Base/Temporary/StaticObjectContainer.php', $staticObjectContainerCode);

		$mockActivePackages = array('FLOW3' => 'PackageObject', 'Foo' => 'PackageObject');
		$mockSettings = array('Foo' => 'Bar');

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->will($this->returnValue($mockSettings));

		$mockDynamicObjectContainer = $this->getMock('F3\FLOW3\Object\Container\DynamicObjectContainer', array(), array(), '', FALSE);

		$mockAopFramework = $this->getMock('F3\FLOW3\AOP\Framework', array(), array(), '', FALSE);
		$mockAopFramework->expects($this->once())->method('loadProxyClasses');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('get', 'buildPackageObjectConfigurations'));
		$objectManager->expects($this->once())->method('get')->with('F3\FLOW3\AOP\Framework')->will($this->returnValue($mockAopFramework));

		$objectManager->injectConfigurationManager($mockConfigurationManager);
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->_set('staticObjectContainerPathAndFilename', 'vfs://Base/Temporary/StaticObjectContainer.php');
		$objectManager->_set('objectContainer', $mockDynamicObjectContainer);

		$objectManager->initializeObjectContainer($mockActivePackages);

		$this->assertType($this->mockStaticObjectContainerClassName, $objectManager->_get('objectContainer'));
		$this->assertTrue(defined($id));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initalizeSessionInitializesTheSessionScopeOfTheObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface', array(), array(), '', FALSE);
		$mockObjectContainer->expects($this->once())->method('initializeSession');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', get_class($mockObjectContainer));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$objectManager->initializeSession();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initalizeSessionSetsTheSessionInitializedFlagCorrectly() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface', array(), array(), '', FALSE);
		$mockObjectContainer->expects($this->once())->method('initializeSession');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', get_class($mockObjectContainer));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$objectManager->initializeSession();

		$this->assertTrue($objectManager->isSessionInitialized());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultContextIsDevelopment() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$objectManager = new \F3\FLOW3\Object\ObjectManager($mockReflectionService);
		$this->assertEquals('Development', $objectManager->getContext(), 'getContext() did not return "Development".');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContextAllowsForSettingTheContext() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$objectManager = new \F3\FLOW3\Object\ObjectManager($mockReflectionService);
		$objectManager->setContext('halululu');
		$this->assertEquals('halululu', $objectManager->getContext(), 'getContext() did not return the context we set.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getForwardsTheCallToTheCurrentObjectContainer() {
		$expectedObject = new \stdClass();

		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('get')->with('someObjectName', 'someArgument')->will($this->returnValue($expectedObject));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$actualObject = $objectManager->get('someObjectName', 'someArgument');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createForwardsTheCallToTheCurrentObjectContainer() {
		$expectedObject = new \stdClass();

		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('create')->with('someObjectName', 'someArgument')->will($this->returnValue($expectedObject));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$actualObject = $objectManager->create('someObjectName', 'someArgument');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function recreateForwardsTheCallToTheCurrentObjectContainer() {
		$expectedObject = new \stdClass();

		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('recreate')->with('someObjectName')->will($this->returnValue($expectedObject));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$actualObject = $objectManager->recreate('someObjectName');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isRegisteredForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('isRegistered')->with('someObjectName')->will($this->returnValue(TRUE));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertTrue($objectManager->isRegistered('someObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitiveObjectNameForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('getCaseSensitiveObjectName')->with('SOMEObjectName')->will($this->returnValue('someObjectName'));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertSame('someObjectName', $objectManager->getCaseSensitiveObjectName('SOMEObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectNameByClassNameForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('getObjectNameByClassName')->with('SomeClassName')->will($this->returnValue('SomeObjectName'));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertSame('SomeObjectName', $objectManager->getObjectNameByClassName('SomeClassName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassNameByObjectNameForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('getClassNameByObjectName')->with('SomeObjectName')->will($this->returnValue('SomeClassName'));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertSame('SomeClassName', $objectManager->getClassNameByObjectName('SomeObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScopeForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('getScope')->with('SomeObjectName')->will($this->returnValue('Prototype'));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertSame('Prototype', $objectManager->getScope('SomeObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushStaticObjectContainerUnlinksTheStaticObjectContainerFile() {
		mkdir('vfs://Base/Temporary');
		file_put_contents('vfs://Base/Temporary/StaticObjectContainer.php', 'x');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerPathAndFilename', 'vfs://Base/Temporary/StaticObjectContainer.php');

		$objectManager->flushStaticObjectContainer('fileHasChanged', 'FLOW3_ClassFiles', array('vfs://Base/Temporary/StaticObjectContainer.php'));
		$this->assertFileNotExists('vfs://Base/Temporary/StaticObjectContainer.php');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdownForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('shutdown');

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$objectManager->shutdown();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @deprecated since 1.0.0 alpha 8
	 */
	public function isObjectRegisteredForwardsTheCallToTheCurrentObjectContainer() {
		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('isRegistered')->with('someObjectName')->will($this->returnValue(TRUE));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$this->assertTrue($objectManager->isObjectRegistered('someObjectName'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @deprecated since 1.0.0 alpha 8
	 */
	public function getObjectForwardsTheCallToTheCurrentObjectContainer() {
		$expectedObject = new \stdClass();

		$mockObjectContainer = $this->getMock('F3\FLOW3\Object\Container\StaticObjectContainerInterface');
		$mockObjectContainer->expects($this->once())->method('get')->with('someObjectName', 'someArgument')->will($this->returnValue($expectedObject));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('dummy'));
		$objectManager->_set('staticObjectContainerClassName', $this->mockStaticObjectContainerClassName);
		$objectManager->_set('objectContainer', $mockObjectContainer);

		$actualObject = $objectManager->getObject('someObjectName', 'someArgument');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildPackageObjectConfigurationsBuildsObjectConfigurationsOfClassesAndInterfacesFromTheGivenPackages() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);

		$className1 = 'FooClass' . uniqid();
		$className2 = 'FooClass' . uniqid();
		$className3 = 'BarClass' . uniqid();
		$className4 = 'BarClass' . uniqid();
		eval('class ' . $className1 . ' {}');
		eval('class ' . $className2 . ' {}');
		eval('class ' . $className3 . ' {}');
		eval('class ' . $className4 . ' {}');

		$mockReflectionService->expects($this->at(1))->method('isClassTaggedWith')->with('DateTime', 'autowiring')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(3))->method('isClassTaggedWith')->with($className1, 'autowiring')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->at(4))->method('getClassTagValues')->with($className1, 'autowiring')->will($this->returnValue(array('off')));
		$mockReflectionService->expects($this->at(9))->method('isClassTaggedWith')->with($className4, 'scope')->will($this->returnValue(TRUE));
		$mockReflectionService->expects($this->at(10))->method('getClassTagValues')->with($className4, 'scope')->will($this->returnValue(array('session')));

		$objectConfigurations = array(
			'Foo' => array($className1 => array('scope' => 'prototype')),
			'Bar' => array($className3 => array('className' => 'Baz'))
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));
		$mockConfigurationManager->expects($this->at(1))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'Bar')
			->will($this->returnValue($objectConfigurations['Bar']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className1 => 'Class1.php', $className2 => 'Class2.php')));

		$packages['Bar'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Bar']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className3 => 'Class3.php', $className4 => 'Class4.php')));

		$objectManager = $this->getAccessibleMock('F3\FLOW3\Object\ObjectManager', array('get'), array(), '', FALSE);
		$objectManager->expects($this->once())->method('get')->with('F3\FLOW3\Reflection\ReflectionService')->will($this->returnValue($mockReflectionService));
		$objectManager->_set('settings', array('object' => array('registerFunctionalTestClasses' => FALSE)));
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$actualObjectConfigurations = $objectManager->_call('buildPackageObjectConfigurations', $packages);

		$this->assertSame(array('DateTime', $className1, $className2, $className3, $className4), array_keys($actualObjectConfigurations));
		$this->assertSame($className1, $actualObjectConfigurations[$className1]->getClassName());
		$this->assertSame(\F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF, $actualObjectConfigurations[$className1]->getAutowiring());
		$this->assertSame(\F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE, $actualObjectConfigurations[$className1]->getScope());
		$this->assertSame('Baz', $actualObjectConfigurations[$className3]->getClassName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildPackageObjectConfigurationsAlsoRegistersInterfacesAsObjectTypes() {
		$className1 = 'FooClass' . uniqid();
		$className2 = 'Foo' . uniqid() . 'Interface';
		$className3 = 'Foo' . uniqid() . 'Interface';
		eval('class ' . $className1 . ' {}');
		eval('interface ' . $className2 . ' {}');
		eval('interface ' . $className3 . ' {}');

		$objectConfigurations = array(
			'Foo' => array(),
		);

		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->at(2))->method('isClassTaggedWith')->with($className1, 'scope')->will($this->returnValue(FALSE));
		$mockReflectionService->expects($this->at(4))->method('getDefaultImplementationClassNameForInterface')->with($className2)->will($this->returnValue($className1));
		$mockReflectionService->expects($this->at(7))->method('getDefaultImplementationClassNameForInterface')->with($className3)->will($this->returnValue(FALSE));

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')
			->will($this->returnValue(array($className1 => 'Class1.php', $className2 => 'Interface2.php', $className3 => 'Interface3.php')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\ObjectManager'), array('get'), array(), '', FALSE);
		$objectManager->expects($this->once())->method('get')->with('F3\FLOW3\Reflection\ReflectionService')->will($this->returnValue($mockReflectionService));
		$objectManager->_set('settings', array('object' => array('registerFunctionalTestClasses' => FALSE)));
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$actualObjectConfigurations = $objectManager->_call('buildPackageObjectConfigurations', $packages);

		$this->assertSame(array('DateTime', $className1, $className2), array_keys($actualObjectConfigurations));
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Object\Exception\InvalidObjectConfigurationException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildPackageObjectConfigurationsThrowsExceptionOnTryingToConfigureNonRegisteredObjects() {
		$mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService', array(), array(), '', FALSE);
		$mockReflectionService->expects($this->any())->method('isAbstract')->will($this->returnValue(FALSE));

		$className1 = 'FooClass' . uniqid();
		eval('class ' . $className1 . ' {}');

		$objectConfigurations = array(
			'Foo' => array('Nemo' => array('scope' => 'prototype'))
		);

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array(), array(), '', FALSE);
		$mockConfigurationManager->expects($this->at(0))->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'Foo')
			->will($this->returnValue($objectConfigurations['Foo']));

		$packages = array();
		$packages['Foo'] = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$packages['Foo']->expects($this->once())->method('getClassFiles')->will($this->returnValue(array($className1 => 'Class1.php')));

		$objectManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Object\ObjectManager'), array('get'), array(), '', FALSE);
		$objectManager->expects($this->once())->method('get')->with('F3\FLOW3\Reflection\ReflectionService')->will($this->returnValue($mockReflectionService));
		$objectManager->_set('settings', array('object' => array('registerFunctionalTestClasses' => FALSE)));
		$objectManager->injectConfigurationManager($mockConfigurationManager);

		$objectManager->_call('buildPackageObjectConfigurations', $packages);
	}


}
?>