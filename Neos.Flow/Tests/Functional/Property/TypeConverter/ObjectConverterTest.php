<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\ObjectConverter;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\InterfaceAImplementation;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClass;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClassWithSingletonConstructorInjection;
use Neos\Flow\Tests\Functional\Property\Fixtures\TestClassWithThirdPartyClassConstructorInjection;
use Neos\Flow\Tests\FunctionalTestCase;
use Neos\Utility\ObjectAccess;
use PHPUnit\Framework\Attributes\Test;

/**
 */
final class ObjectConverterTest extends FunctionalTestCase
{
    /**
     * @var ObjectConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = $this->objectManager->get(ObjectConverter::class);
    }

    #[Test]
    public function getTypeOfChildPropertyImmediatelyReturnsConfiguredTargetTypeIfSetSo()
    {
        $propertyName = 'somePropertyName';
        $expectedTargetType = 'someExpectedTargetType';
        $configuration = new PropertyMappingConfiguration();
        $configuration
            ->forProperty($propertyName)
            ->setTypeConverterOption(
                ObjectConverter::class,
                ObjectConverter::CONFIGURATION_TARGET_TYPE,
                $expectedTargetType
            );

        $actual = $this->converter->getTypeOfChildProperty('irrelevant', $propertyName, $configuration);
        self::assertEquals($expectedTargetType, $actual);
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsCorrectTypeIfAConstructorArgumentForThatPropertyIsPresent()
    {
        $actual = $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'dummy',
            new PropertyMappingConfiguration()
        );
        self::assertEquals('float', $actual);
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsCorrectTypeIfASetterForThatPropertyIsPresent()
    {
        $actual = $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'attributeWithStringTypeAnnotation',
            new PropertyMappingConfiguration()
        );
        self::assertEquals('string', $actual);
    }

    #[Test]
    public function getTypeOfChildPropertyThrowsExceptionIfThatPropertyIsPubliclyPresentButHasNoProperTypeAnnotation()
    {
        $this->expectExceptionCode(1406821818);
        $this->expectException(InvalidTargetException::class);
        $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'somePublicPropertyWithoutVarAnnotation',
            new PropertyMappingConfiguration()
        );
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsCorrectTypeIfThatPropertyIsPubliclyPresent()
    {
        $configuration = new PropertyMappingConfiguration();
        $actual = $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'somePublicProperty',
            $configuration
        );
        self::assertEquals('float', $actual);
    }

    #[Test]
    public function convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic()
    {
        $convertedObject = $this->converter->convertFrom(
            'irrelevant',
            TestClass::class,
            [
                'propertyMeantForConstructorUsage' => 'theValue',
                'propertyMeantForSetterUsage' => 'theValue',
                'propertyMeantForPublicUsage' => 'theValue'
            ],
            new PropertyMappingConfiguration()
        );

        self::assertEquals('theValue set via Constructor', ObjectAccess::getProperty($convertedObject, 'propertyMeantForConstructorUsage', true));
        self::assertEquals('theValue set via Setter', ObjectAccess::getProperty($convertedObject, 'propertyMeantForSetterUsage', true));
        self::assertEquals('theValue', ObjectAccess::getProperty($convertedObject, 'propertyMeantForPublicUsage', true));
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsNullIfPropertyDoesNotExistAndSkipUnknownPropertiesIsSet()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->skipUnknownProperties();

        $result = $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'someUnknownProperty',
            $configuration
        );
        self::assertNull($result);
    }

    #[Test]
    public function getTypeOfChildPropertyReturnsNullIfPropertyDoesNotExistAndPropertyIsFlaggedToBeSkippedSpecifically()
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->skipProperties('someUnknownProperty');

        $result = $this->converter->getTypeOfChildProperty(
            TestClass::class,
            'someUnknownProperty',
            $configuration
        );
        self::assertNull($result);
    }

    #[Test]
    public function convertFromAllowsAutomaticInjectionOfSingletonConstructorArguments()
    {
        $convertedObject = $this->converter->convertFrom(
            'irrelevant',
            TestClassWithSingletonConstructorInjection::class
        );
        self::assertInstanceOf(InterfaceAImplementation::class, $convertedObject->getSingletonClass());
    }

    #[Test]
    public function convertFromThrowsMeaningfulExceptionWhenTheTargetExpectsAnUnknownDependencyThatIsNotSpecifiedInTheSource()
    {
        $this->expectException(InvalidTargetException::class);
        $this->converter->convertFrom(
            'irrelevant',
            TestClassWithThirdPartyClassConstructorInjection::class
        );
    }
}
