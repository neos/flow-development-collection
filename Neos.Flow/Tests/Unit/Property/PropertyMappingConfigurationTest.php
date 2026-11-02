<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

require_once(__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 */
#[CoversClass(\Neos\Flow\Property\PropertyMappingConfiguration::class)]
#[CoversMethod(\Neos\Flow\Property\PropertyMappingConfiguration::class, 'getTargetPropertyName')]
#[CoversMethod(\Neos\Flow\Property\PropertyMappingConfiguration::class, 'shouldMap')]
#[CoversMethod(\Neos\Flow\Property\PropertyMappingConfiguration::class, 'shouldSkip')]
final class PropertyMappingConfigurationTest extends UnitTestCase
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

    #[Test]
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        self::assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
        self::assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
    }

    #[Test]
    public function shouldMapReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    #[Test]
    public function shouldMapReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->allowAllProperties();
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
    }

    #[Test]
    public function shouldMapReturnsTrueForAllowedProperties()
    {
        $this->propertyMappingConfiguration->allowProperties('someSourceProperty', 'someOtherProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));
    }

    #[Test]
    public function shouldMapReturnsFalseForExcludedProperties()
    {
        $this->propertyMappingConfiguration->allowAllPropertiesExcept('someSourceProperty', 'someOtherProperty');
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));

        self::assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherPropertyWhichHasNotBeenConfigured'));
    }

    #[Test]
    public function shouldSkipReturnsFalseByDefault()
    {
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertFalse($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    #[Test]
    public function shouldSkipReturnsTrueIfConfigured()
    {
        $this->propertyMappingConfiguration->skipProperties('someSourceProperty', 'someOtherSourceProperty');
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someSourceProperty'));
        self::assertTrue($this->propertyMappingConfiguration->shouldSkip('someOtherSourceProperty'));
    }

    #[Test]
    public function setTypeConverterOptionsCanBeRetrievedAgain(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));

        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        self::assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    #[Test]
    public function nonexistentTypeConverterOptionsReturnNull(): void
    {
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
    }

    #[Test]
    public function setTypeConverterOptionsShouldOverrideAlreadySetOptions(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k3' => 'v3']);

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k3'));
        self::assertNull($this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    #[Test]
    public function setTypeConverterOptionShouldOverrideAlreadySetOptions(): void
    {
        $mockTypeConverterClass = get_class($this->createMock(TypeConverterInterface::class));
        $this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, ['k1' => 'v1', 'k2' => 'v2']);
        $this->propertyMappingConfiguration->setTypeConverterOption($mockTypeConverterClass, 'k1', 'v3');

        self::assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
        self::assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
    }

    #[Test]
    public function getTypeConverterReturnsNullIfNoTypeConverterSet()
    {
        self::assertNull($this->propertyMappingConfiguration->getTypeConverter());
    }

    #[Test]
    public function getTypeConverterReturnsTypeConverterIfItHasBeenSet()
    {
        $mockTypeConverter = $this->createStub(TypeConverterInterface::class);
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

    #[Test]
    public function getTargetPropertyNameShouldRespectMapping()
    {
        $this->propertyMappingConfiguration->setMapping('k1', 'k1a');
        self::assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
        self::assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
    }

    /**
     * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
     */
    public static function fluentInterfaceMethodsDataProvider(): array
    {
        return [
            ['allowAllProperties'],
            ['allowProperties'],
            ['allowAllPropertiesExcept'],
            ['setMapping', ['k1', 'k1a']],
            ['setTypeConverterOptions', ['__mock_class:' . TypeConverterInterface::class, ['k1' => 'v1', 'k2' => 'v2']]],
            ['setTypeConverterOption', ['__mock_class:' . TypeConverterInterface::class, 'k1', 'v3']],
            ['setTypeConverter', ['__stub:' . TypeConverterInterface::class]],
        ];
    }

    #[DataProvider('fluentInterfaceMethodsDataProvider')]
    #[Test]
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = [])
    {
        foreach ($argumentsForMethod as $i => $arg) {
            if (is_string($arg) && str_starts_with($arg, '__mock_class:')) {
                $argumentsForMethod[$i] = get_class($this->createMock(substr($arg, 13)));
            } elseif (is_string($arg) && str_starts_with($arg, '__stub:')) {
                $argumentsForMethod[$i] = $this->createStub(substr($arg, 7));
            }
        }
        $actualResult = call_user_func_array([$this->propertyMappingConfiguration, $methodToTestForFluentInterface], $argumentsForMethod);
        self::assertSame($this->propertyMappingConfiguration, $actualResult);
    }

    #[Test]
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithGetConfigurationFor()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->getConfigurationFor('items')->getConfigurationFor('6');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    #[Test]
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithForProperty()
    {
        // using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
        $this->propertyMappingConfiguration->forProperty('items.*.foo')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items.6.foo');
        self::assertSame('v1', $configuration->getConfigurationValue(\stdClass::class, 'k1'));
    }

    #[Test]
    public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithShouldMap()
    {
        $this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', ['k1' => 'v1']);

        $configuration = $this->propertyMappingConfiguration->forProperty('items');
        self::assertTrue($configuration->shouldMap(6));
    }
}
