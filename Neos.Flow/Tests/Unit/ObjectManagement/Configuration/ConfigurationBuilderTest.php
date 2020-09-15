<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Configuration;

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
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationBuilder;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationProperty;
use Neos\Flow\ObjectManagement\Exception;
use Neos\Flow\ObjectManagement\Exception\InvalidObjectConfigurationException;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;

/**
 * Testcase for the object configuration builder
 *
 */
class ConfigurationBuilderTest extends UnitTestCase
{
    /**
     * @test
     */
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

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration, 'The manually created and the built object configuration don\'t match.');
    }

    /**
     * @test
     */
    public function argumentsOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions()
    {
        $configurationArray = [];
        $configurationArray['arguments'][1]['object']['name'] = 'Foo';
        $configurationArray['arguments'][1]['object']['className'] = __CLASS__;

        $argumentObjectConfiguration = new Configuration('Foo', __CLASS__);
        $argumentObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', argument "1"');

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setArgument(new ConfigurationArgument(1, $argumentObjectConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT));

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    /**
     * @test
     */
    public function propertiesOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions()
    {
        $configurationArray = [];
        $configurationArray['properties']['theProperty']['object']['name'] = 'Foo';
        $configurationArray['properties']['theProperty']['object']['className'] = __CLASS__;

        $propertyObjectConfiguration = new Configuration('Foo', __CLASS__);
        $propertyObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ', property "theProperty"');

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setProperty(new ConfigurationProperty('theProperty', $propertyObjectConfiguration, ConfigurationProperty::PROPERTY_TYPES_OBJECT));

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    /**
     * @test
     */
    public function itIsPossibleToPassArraysAsStraightArgumentOrPropertyValues()
    {
        $configurationArray = [];
        $configurationArray['properties']['straightValueProperty']['value'] = ['foo' => 'bar', 'object' => 'nö'];
        $configurationArray['arguments'][1]['value'] = ['foo' => 'bar', 'object' => 'nö'];

        $objectConfiguration = new Configuration('TestObject', 'TestObject');
        $objectConfiguration->setProperty(new ConfigurationProperty('straightValueProperty', ['foo' => 'bar', 'object' => 'nö']));
        $objectConfiguration->setArgument(new ConfigurationArgument(1, ['foo' => 'bar', 'object' => 'nö']));

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
        self::assertEquals($objectConfiguration, $builtObjectConfiguration);
    }

    /**
     * @test
     */
    public function invalidOptionResultsInException()
    {
        $this->expectException(InvalidObjectConfigurationException::class);
        $configurationArray = ['scoopy' => 'prototype'];
        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);
    }

    /**
     * @test
     */
    public function privatePropertyAnnotatedForInjectionThrowsException()
    {
        $this->expectException(Exception::class);
        $configurationArray = [];
        $configurationArray['arguments'][1]['setting'] = 'Neos.Foo.Bar';
        $configurationArray['properties']['someProperty']['setting'] = 'Neos.Bar.Baz';

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $dummyObjectConfiguration = [$configurationBuilder->_call('parseConfigurationArray', __CLASS__, $configurationArray, __CLASS__)];

        $reflectionServiceMock = $this->createMock(ReflectionService::class);
        $reflectionServiceMock
                ->expects(self::once())
                ->method('getPropertyNamesByAnnotation')
                ->with(__CLASS__, Flow\Inject::class)
                ->will(self::returnValue(['dummyProperty']));

        $reflectionServiceMock
                ->expects(self::once())
                ->method('isPropertyPrivate')
                ->with(__CLASS__, 'dummyProperty')
                ->will(self::returnValue(true));

        $configurationBuilder->injectReflectionService($reflectionServiceMock);
        $configurationBuilder->_callRef('autowireProperties', $dummyObjectConfiguration);
    }

    /**
     * @test
     */
    public function errorOnGetClassMethodsThrowsException()
    {
        $this->expectException(Exception\UnknownClassException::class);
        $configurationArray = [];
        $configurationArray['properties']['someProperty']['object']['name'] = 'Foo';
        $configurationArray['properties']['someProperty']['object']['className'] = 'foobar';

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $dummyObjectConfiguration = [$configurationBuilder->_call('parseConfigurationArray', 'Foo', $configurationArray, __CLASS__)];

        $configurationBuilder->_callRef('autowireProperties', $dummyObjectConfiguration);
    }

    /**
     * @test
     */
    public function parseConfigurationArrayBuildsConfigurationPropertyForInjectedSetting()
    {
        $configurationArray = [];
        $configurationArray['properties']['someProperty']['setting'] = 'Neos.Foo.Bar';

        /** @var ConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, null);
        /** @var Configuration $builtObjectConfiguration */
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);

        $expectedConfigurationProperty = new ConfigurationProperty('someProperty', ['type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => 'Neos.Foo.Bar'], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
        self::assertEquals($expectedConfigurationProperty, $builtObjectConfiguration->getProperties()['someProperty']);
    }

    /**
     * @test
     */
    public function parseConfigurationArrayBuildsConfigurationArgumentForInjectedSetting()
    {
        $configurationArray = [];
        $configurationArray['arguments'][1]['setting'] = 'Neos.Foo.Bar';

        /** @var ConfigurationBuilder $configurationBuilder */
        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, null);
        /** @var Configuration $builtObjectConfiguration */
        $builtObjectConfiguration = $configurationBuilder->_call('parseConfigurationArray', 'TestObject', $configurationArray, __CLASS__);

        $expectedConfigurationArgument = new ConfigurationArgument(1, 'Neos.Foo.Bar', ConfigurationArgument::ARGUMENT_TYPES_SETTING);
        self::assertEquals($expectedConfigurationArgument, $builtObjectConfiguration->getArguments()[1]);
    }

    /**
     * @test
     */
    public function objectsCreatedByFactoryShouldNotFailOnMissingConstructorArguments()
    {
        $configurationArray = [
            'scope' => 'singleton',
            'factoryObjectName' => 'TestFactory',
        ];

        $configurationBuilder = $this->getAccessibleMock(ConfigurationBuilder::class, ['dummy']);
        $dummyObjectConfiguration = [$configurationBuilder->_call('parseConfigurationArray', __CLASS__, $configurationArray)];

        $reflectionServiceMock = $this->createMock(ReflectionService::class);

        $reflectionServiceMock
            ->method('hasMethod')
            ->with(__CLASS__, '__construct')
            ->will($this->returnValue(true));

        $reflectionServiceMock
            ->method('getMethodParameters')
            ->with(__CLASS__, '__construct')
            ->will($this->returnValue([
                'testArray' => [
                    'position' => 0,
                    'optional' => false,
                    'class' => null,
                    'allowsNull' => false
                ]
            ]));

        $configurationBuilder->injectReflectionService($reflectionServiceMock);
        try {
            $configurationBuilder->_callRef('autowireArguments', $dummyObjectConfiguration);
        } catch (UnresolvedDependenciesException $e) {
            self::fail('Factory created objects should not throw UnresolvedDependenciesException by autowiring constructor arguments');
        }
        self::assertEquals([], $dummyObjectConfiguration[0]->getArguments());
    }
}
