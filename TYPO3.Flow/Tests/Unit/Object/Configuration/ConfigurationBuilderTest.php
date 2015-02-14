<?php
namespace TYPO3\Flow\Tests\Unit\Object\Configuration;

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
use TYPO3\Flow\Object\Configuration\Configuration;
use TYPO3\Flow\Object\Configuration\ConfigurationArgument;
use TYPO3\Flow\Object\Configuration\ConfigurationBuilder;
use TYPO3\Flow\Object\Configuration\ConfigurationProperty;

/**
 * Testcase for the object configuration builder
 *
 */
class ConfigurationBuilderTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function allBasicOptionsAreSetCorrectly() {
		$factoryObjectName = 'ConfigurationBuilderTest' . md5(uniqid(mt_rand(), TRUE));
		eval('class ' . $factoryObjectName . ' { public function manufacture() {} } ');

		$configurationArray = array();
		$configurationArray['scope'] = 'prototype';
		$configurationArray['className'] = __CLASS__;
		$configurationArray['factoryObjectName'] = $factoryObjectName;
		$configurationArray['factoryMethodName'] = 'manufacture';
		$configurationArray['lifecycleInitializationMethodName'] = 'initializationMethod';
		$configurationArray['lifecycleShutdownMethodName'] = 'shutdownMethod';
		$configurationArray['autowiring'] = FALSE;

		$objectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('TestObject', __CLASS__);
		$objectConfiguration->setScope(\TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE);
		$objectConfiguration->setClassName(__CLASS__);
		$objectConfiguration->setFactoryObjectName($factoryObjectName);
		$objectConfiguration->setFactoryMethodName('manufacture');
		$objectConfiguration->setLifecycleInitializationMethodName('initializationMethod');
		$objectConfiguration->setLifecycleShutdownMethodName('shutdownMethod');
		$objectConfiguration->setAutowiring(\TYPO3\Flow\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF);

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration, 'The manually created and the built object configuration don\'t match.');
	}

	/**
	 * @test
	 */
	public function argumentsOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['object']['name'] = 'Foo';
		$configurationArray['arguments'][1]['object']['className'] = __CLASS__;

		$argumentObjectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('Foo', __CLASS__);
		$argumentObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', argument "1"');

		$objectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setArgument(new \TYPO3\Flow\Object\Configuration\ConfigurationArgument(1, $argumentObjectConfiguration, \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 */
	public function propertiesOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions() {
		$configurationArray = array();
		$configurationArray['properties']['theProperty']['object']['name'] = 'Foo';
		$configurationArray['properties']['theProperty']['object']['className'] = __CLASS__;

		$propertyObjectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('Foo', __CLASS__);
		$propertyObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', property "theProperty"');

		$objectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \TYPO3\Flow\Object\Configuration\ConfigurationProperty('theProperty', $propertyObjectConfiguration, \TYPO3\Flow\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToPassArraysAsStraightArgumentOrPropertyValues() {
		$configurationArray = array();
		$configurationArray['properties']['straightValueProperty']['value'] = array('foo' => 'bar', 'object' => 'nö');
		$configurationArray['arguments'][1]['value'] = array('foo' => 'bar', 'object' => 'nö');

		$objectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \TYPO3\Flow\Object\Configuration\ConfigurationProperty('straightValueProperty', array('foo' => 'bar', 'object' => 'nö')));
		$objectConfiguration->setArgument(new \TYPO3\Flow\Object\Configuration\ConfigurationArgument(1, array('foo' => 'bar', 'object' => 'nö')));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException
	 */
	public function invalidOptionResultsInException() {
		$configurationArray = array('scoopy' => 'prototype');
		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Object\Exception
	 */
	public function privatePropertyAnnotatedForInjectionThrowsException() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['setting'] = 'TYPO3.Foo.Bar';
		$configurationArray['properties']['someProperty']['setting'] = 'TYPO3.Bar.Baz';

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$dummyObjectConfiguration = array($configurationBuilder->_call('parseConfigurationArray', __CLASS__, $configurationArray, __CLASS__));

		$reflectionServiceMock = $this->getMock('\TYPO3\Flow\Reflection\ReflectionService');
		$reflectionServiceMock
				->expects($this->once())
				->method('getPropertyNamesByAnnotation')
				->with(__CLASS__, 'TYPO3\Flow\Annotations\Inject')
				->will($this->returnValue(array('dummyProperty')));

		$reflectionServiceMock
				->expects($this->once())
				->method('isPropertyPrivate')
				->with(__CLASS__, 'dummyProperty')
				->will($this->returnValue(TRUE));

		$configurationBuilder->injectReflectionService($reflectionServiceMock);
		$configurationBuilder->_callRef('autowireProperties', $dummyObjectConfiguration);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Object\Exception\UnknownClassException
	 */
	public function errorOnGetClassMethodsThrowsException() {
		$configurationArray = array();
		$configurationArray['properties']['someProperty']['object']['name'] = 'Foo';
		$configurationArray['properties']['someProperty']['object']['className'] = 'foobar';

		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$dummyObjectConfiguration = array($configurationBuilder->_call('parseConfigurationArray', 'Foo', $configurationArray, __CLASS__));

		$configurationBuilder->_callRef('autowireProperties', $dummyObjectConfiguration);
	}

	/**
	 * @test
	 */
	public function parseConfigurationArrayBuildsConfigurationPropertyForInjectedSetting() {
		$configurationArray = array();
		$configurationArray['properties']['someProperty']['setting'] = 'TYPO3.Foo.Bar';


		/** @var ConfigurationBuilder $configurationBuilder */
		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', NULL);
		/** @var Configuration $builtObjectConfiguration */
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);

		$expectedConfigurationProperty = new ConfigurationProperty('someProperty', array('type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => 'TYPO3.Foo.Bar'), ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
		$this->assertEquals($expectedConfigurationProperty, $builtObjectConfiguration->getProperties()['someProperty']);
	}

	/**
	 * @test
	 */
	public function parseConfigurationArrayBuildsConfigurationArgumentForInjectedSetting() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['setting'] = 'TYPO3.Foo.Bar';

		/** @var ConfigurationBuilder $configurationBuilder */
		$configurationBuilder = $this->getAccessibleMock('TYPO3\Flow\Object\Configuration\ConfigurationBuilder', NULL);
		/** @var Configuration $builtObjectConfiguration */
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);

		$expectedConfigurationArgument = new ConfigurationArgument(1, 'TYPO3.Foo.Bar', ConfigurationArgument::ARGUMENT_TYPES_SETTING);
		$this->assertEquals($expectedConfigurationArgument, $builtObjectConfiguration->getArguments()[1]);
	}

}
