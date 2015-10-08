<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\ObjectConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 */
class ObjectConverterTest extends FunctionalTestCase
{
    /**
     * @var ObjectConverter
     */
    protected $converter;

    public function setUp()
    {
        parent::setUp();
        $this->converter = $this->objectManager->get(\TYPO3\Flow\Property\TypeConverter\ObjectConverter::class);
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyImmediatelyReturnsConfiguredTargetTypeIfSetSo()
    {
        $propertyName = 'somePropertyName';
        $expectedTargetType = 'someExpectedTargetType';
        $configuration = new PropertyMappingConfiguration();
        $configuration
            ->forProperty($propertyName)
            ->setTypeConverterOption(
                \TYPO3\Flow\Property\TypeConverter\ObjectConverter::class,
                ObjectConverter::CONFIGURATION_TARGET_TYPE,
                $expectedTargetType);

        $actual = $this->converter->getTypeOfChildProperty('irrelevant', $propertyName, $configuration);
        $this->assertEquals($expectedTargetType, $actual);
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsCorrectTypeIfAConstructorArgumentForThatPropertyIsPresent()
    {
        $actual = $this->converter->getTypeOfChildProperty(
            \TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass::class,
            'dummy',
            new PropertyMappingConfiguration()
        );
        $this->assertEquals('float', $actual);
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsCorrectTypeIfASetterForThatPropertyIsPresent()
    {
        $actual = $this->converter->getTypeOfChildProperty(
            \TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass::class,
            'attributeWithStringTypeAnnotation',
            new PropertyMappingConfiguration()
        );
        $this->assertEquals('string', $actual);
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyThrowsExceptionIfThatPropertyIsPubliclyPresentButHasNoProperTypeAnnotation()
    {
        $this->setExpectedException(\TYPO3\Flow\Property\Exception\InvalidTargetException::class, '', 1406821818);
        $this->converter->getTypeOfChildProperty(
            \TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass::class,
            'somePublicPropertyWithoutVarAnnotation',
            new PropertyMappingConfiguration()
        );
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsCorrectTypeIfThatPropertyIsPubliclyPresent()
    {
        $configuration = new PropertyMappingConfiguration();
        $actual = $this->converter->getTypeOfChildProperty(
            \TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass::class,
            'somePublicProperty',
            $configuration
        );
        $this->assertEquals('float', $actual);
    }

    /**
     * @test
     */
    public function convertFromUsesAppropriatePropertyPopulationMethodsInOrderConstructorSetterPublic()
    {
        $convertedObject = $this->converter->convertFrom(
            'irrelevant',
            \TYPO3\Flow\Tests\Functional\Property\Fixtures\TestClass::class,
            array(
                'propertyMeantForConstructorUsage' => 'theValue',
                'propertyMeantForSetterUsage' => 'theValue',
                'propertyMeantForPublicUsage' => 'theValue'
            ),
            new PropertyMappingConfiguration()
        );

        $this->assertEquals('theValue set via Constructor', ObjectAccess::getProperty($convertedObject, 'propertyMeantForConstructorUsage', true));
        $this->assertEquals('theValue set via Setter', ObjectAccess::getProperty($convertedObject, 'propertyMeantForSetterUsage', true));
        $this->assertEquals('theValue', ObjectAccess::getProperty($convertedObject, 'propertyMeantForPublicUsage', true));
    }
}
