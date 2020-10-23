<?php
namespace Neos\Flow\Tests\Unit\Property;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;

require_once(__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @covers \Neos\Flow\Property\PropertyMappingConfiguration
 */
class PropertyMappingConfigurationTest extends UnitTestCase
{
    /**
     * @var PropertyMappingConfiguration
     */
    protected $propertyMappingConfiguration;

    /**
     * Initialization
     */
    protected function setUp(): void
    {
        $this->propertyMappingConfiguration = new PropertyMappingConfiguration();
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::getTargetPropertyName
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        self::assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
        self::assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->allowAllProperties();
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsTrueForAllowedProperties()
    {
        $this->propertyMappingConfiguration->allowProperties('someSourceProperty', 'someOtherProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldMap
     */
    public function shouldMapReturnsFalseForExcludedProperties()
    {
        $this->propertyMappingConfiguration->allowAllPropertiesExcept('someSourceProperty', 'someOtherProperty');
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));

        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherPropertyWhichHasNotBeenConfigured'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldSkip
     */
    public function shouldSkipReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     * @covers \Neos\Flow\Property\PropertyMappingConfiguration::shouldSkip
     */
    public function shouldSkipReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->skipProperties('someSourceProperty', 'someOtherSourceProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsCanBeRetrievedAgain()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);

        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        self::assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function nonexistentTypeConverterOptionsReturnNull()
    {
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionsShouldOverrideAlreadySetOptions()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k3' => 'v3']);

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k3'));
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function setTypeConverterOptionShouldOverrideAlreadySetOptions()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOption($mockTypeConverterClass, 'k1', 'v3');

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsNullIfNoTypeConverterSet()
    {
        self::assertNull($this->propertyMappingConfiguration->getTypeConverter());
    }

    /**
     * @test
     */
    public function getTypeConverterReturnsTypeConverterIfItHasBeenSet()
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $this->propertyMappingConfiguration->setTypeConverter($mockTypeConverter);
        self::assertSame($mockTypeConverter, $this->propertyMappingConfiguration->getTypeConverter());
    }

    /**
     * @return PropertyMappingConfiguration
     */
    protected function buildChildConfigurationForSingleProperty()
    {
        $childConfiguration = $this->propertyMappingConfiguration->forProperty('key1.key2');
        $childConfiguration->setTypeConverterOption('someConverter', 'foo', 'specialChildConverter');

        return $childConfiguration;
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldRespectMapping()
    {
        $this->propertyMappingConfiguration->setMapping('k1', 'k1a');
        self::assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
        self::assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
    }

    /**
     * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
     */
    public function fluentInterfaceMethodsDataProvider()
    {
        $mockTypeConverterClass = $this->getMockClass(TypeConverterInterface::class);

        return [
            ['allowAllProperties'],
            ['allowProperties'],
            ['allowAllPropertiesExcept'],
            ['setMapping', ['k1', 'k1a']],
            ['setTypeConverterOptions', [$mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']]],
            ['setTypeConverterOption', [$mockTypeConverterClass, 'k1', 'v3']],
            ['setTypeConverter', [$this->createMock(TypeConverterInterface::class)]],
        ];
    }

    /**
     * @test
     * @dataProvider fluentInterfaceMethodsDataProvider
     */
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = [])
    {
        $actualResult = call_user_func_array([$this->propertyMappingConfiguration, $methodToTestForFluentInterface], $argumentsForMethod);
        self::assertSame($this->propertyMappingConfiguration, $actualResult);
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithGetConfigurationFor()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->getConfigurationFor('items')->getConfigurationFor('6');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithForProperty()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*.foo')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items.6.foo');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    /**
     * @test
     */
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithShouldMap()
    {
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items');
        self::assertTrue($configuration->shouldMap(6));
    }
}
