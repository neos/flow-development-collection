<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Configuration;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationParser;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationProperty;
use Neos\Flow\ObjectManagement\Exception\InvalidObjectConfigurationException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the object configuration parser
 */
class ConfigurationParserTest extends UnitTestCase
{
    #[Test]
    public function allBasicOptionsAreSetCorrectly()
    {
        $factoryObjectName = 'ConfigurationBuilderTest' . md5(uniqid(mt_rand(), true));
        eval('class ' . $factoryObjectName . ' { public function manufacture() {} } ');

        $configurationArray = [];
        $configurationArray['scope'] = 'prototype';
        $configurationArray['className'] = __CLASS__;
        $configurationArray['factoryObjectName'] = $factoryObjectName;
        $configurationArray['factoryMethodName'] = 'manufacture';
        $configurationArray['lifecycleInitializationMethodName'] = 'initializationMethod';
        $configurationArray['lifecycleShutdownMethodName'] = 'shutdownMethod';
        $configurationArray['autowiring'] = false;

        $objectConfiguration = new Configuration('TestObject', __CLASS__);
        $objectConfiguration->setScope(Configuration::SCOPE_PROTOTYPE);
        $objectConfiguration->setClassName(__CLASS__);
        $objectConfiguration->setFactoryObjectName($factoryObjectName);
        $objectConfiguration->setFactoryMethodName('manufacture');
        $objectConfiguration->setLifecycleInitializationMethodName('initializationMethod');
        $objectConfiguration->setLifecycleShutdownMethodName('shutdownMethod');
        $objectConfiguration->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    #[Test]
    public function argumentsOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions()
    {
        $configurationArray = [];
        $configurationArray['arguments'][1]['object']['name'] = 'Foo';
        $configurationArray['arguments'][1]['object']['className'] = __CLASS__;

        $argumentObjectConfiguration = new Configuration('Foo', __CLASS__);
        $argumentObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', argument "1"');

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setArgument(new ConfigurationArgument(1, $argumentObjectConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT));

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    #[Test]
    public function propertiesOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions()
    {
        $configurationArray = [];
        $configurationArray['properties']['theProperty']['object']['name'] = 'Foo';
        $configurationArray['properties']['theProperty']['object']['className'] = __CLASS__;

        $propertyObjectConfiguration = new Configuration('Foo', __CLASS__);
        $propertyObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', property "theProperty"');

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setProperty(new ConfigurationProperty('theProperty', $propertyObjectConfiguration, ConfigurationProperty::PROPERTY_TYPES_OBJECT));

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    #[Test]
    public function itIsPossibleToPassArraysAsStraightArgumentOrPropertyValues()
    {
        $configurationArray = [];
        $configurationArray['properties']['straightValueProperty']['value'] = ['foo' => 'bar', 'object' => 'nö'];
        $configurationArray['arguments'][1]['value'] = ['foo' => 'bar', 'object' => 'nö'];

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setProperty(new ConfigurationProperty('straightValueProperty', ['foo' => 'bar', 'object' => 'nö']));
        $objectConfiguration->setArgument(new ConfigurationArgument(1, ['foo' => 'bar', 'object' => 'nö']));

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    #[Test]
    public function invalidOptionResultsInException()
    {
        $this->expectException(InvalidObjectConfigurationException::class);
        $configurationArray = ['scoopy' => 'prototype'];
        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);
    }

    #[Test]
    public function parseConfigurationArrayBuildsConfigurationArgumentForInjectedSetting()
    {
        $configurationArray = [];
        $configurationArray['arguments'][1]['setting'] = 'Neos.Foo.Bar';

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);

        $expectedConfigurationArgument = new ConfigurationArgument(1, 'Neos.Foo.Bar', ConfigurationArgument::ARGUMENT_TYPES_SETTING);
        self::assertEquals($expectedConfigurationArgument, $builtObjectConfiguration->getArguments()[1]);
    }

    #[Test]
    public function parseConfigurationArrayBuildsConfigurationPropertyForInjectedSetting()
    {
        $configurationArray = [];
        $configurationArray['properties']['someProperty']['setting'] = 'Neos.Foo.Bar';

        $reflectionServiceMock = $this->getMockBuilder(ReflectionService::class)->getMock();
        $configurationParser = new ConfigurationParser($reflectionServiceMock);
        $builtObjectConfiguration = $configurationParser->parseConfigurationArray('TestObject', $configurationArray, __CLASS__);

        $expectedConfigurationProperty = new ConfigurationProperty('someProperty', ['type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => 'Neos.Foo.Bar'], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
        self::assertEquals($expectedConfigurationProperty, $builtObjectConfiguration->getProperties()['someProperty']);
    }
}
