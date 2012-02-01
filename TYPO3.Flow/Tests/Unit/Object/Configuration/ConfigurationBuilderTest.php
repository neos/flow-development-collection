<?php
namespace TYPO3\FLOW3\Tests\Unit\Object\Configuration;

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
 * Testcase for the object configuration builder
 *
 */
class ConfigurationBuilderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

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

		$objectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('TestObject', __CLASS__);
		$objectConfiguration->setScope(\TYPO3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE);
		$objectConfiguration->setClassName(__CLASS__);
		$objectConfiguration->setFactoryObjectName($factoryObjectName);
		$objectConfiguration->setFactoryMethodName('manufacture');
		$objectConfiguration->setLifecycleInitializationMethodName('initializationMethod');
		$objectConfiguration->setLifecycleShutdownMethodName('shutdownMethod');
		$objectConfiguration->setAutowiring(\TYPO3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF);

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
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

		$argumentObjectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('Foo', __CLASS__);
		$argumentObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', argument "1"');

		$objectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setArgument(new \TYPO3\FLOW3\Object\Configuration\ConfigurationArgument(1, $argumentObjectConfiguration, \TYPO3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
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

		$propertyObjectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('Foo', __CLASS__);
		$propertyObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', property "theProperty"');

		$objectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty('theProperty', $propertyObjectConfiguration, \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
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

		$objectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty('straightValueProperty', array('foo' => 'bar', 'object' => 'nö')));
		$objectConfiguration->setArgument(new \TYPO3\FLOW3\Object\Configuration\ConfigurationArgument(1, array('foo' => 'bar', 'object' => 'nö')));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 */
	public function settingsCanBeInjectedAsArgumentOrProperty() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['setting'] = 'TYPO3.Foo.Bar';
		$configurationArray['properties']['someProperty']['setting'] = 'TYPO3.Bar.Baz';

		$objectConfiguration = new \TYPO3\FLOW3\Object\Configuration\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setArgument(new \TYPO3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'TYPO3.Foo.Bar', \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING));
		$objectConfiguration->setProperty(new \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty('someProperty', 'TYPO3.Bar.Baz', \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING));

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Object\Exception\InvalidObjectConfigurationException
	 */
	public function invalidOptionResultsInException() {
		$configurationArray = array('scoopy' => 'prototype');
		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Object\Exception
	 */
	public function privatePropertyAnnotatedForInjectionThrowsException() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['setting'] = 'TYPO3.Foo.Bar';
		$configurationArray['properties']['someProperty']['setting'] = 'TYPO3.Bar.Baz';

		$configurationBuilder = $this->getAccessibleMock('TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder', array('dummy'));
		$dummyObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', __CLASS__, $configurationArray, __CLASS__);

		$reflectionServiceMock = $this->getMock('\TYPO3\FLOW3\Reflection\ReflectionService');
		$reflectionServiceMock
				->expects($this->once())
				->method('getPropertyNamesByTag')
				->with(__CLASS__, 'inject')
				->will($this->returnValue(array('dummyProperty')));

		$reflectionServiceMock
				->expects($this->once())
				->method('isPropertyPrivate')
				->with(__CLASS__, 'dummyProperty')
				->will($this->returnValue(TRUE));

		$configurationBuilder->injectReflectionService($reflectionServiceMock);
		$configurationBuilder->_call('autowireProperties', array($dummyObjectConfiguration));
	}
}
?>