<?php
namespace TYPO3\Flow\Tests\Functional\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Property\Exception\InvalidTargetException;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\ObjectConverter;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Flow\Tests\Functional\Property\Fixtures;

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
        $this->converter = $this->objectManager->get(ObjectConverter::class);
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
                ObjectConverter::class,
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
            Fixtures\TestClass::class,
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
            Fixtures\TestClass::class,
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
        $this->setExpectedException(InvalidTargetException::class, '', 1406821818);
        $this->converter->getTypeOfChildProperty(
            Fixtures\TestClass::class,
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
            Fixtures\TestClass::class,
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
            Fixtures\TestClass::class,
            [
                'propertyMeantForConstructorUsage' => 'theValue',
                'propertyMeantForSetterUsage' => 'theValue',
                'propertyMeantForPublicUsage' => 'theValue'
            ],
            new PropertyMappingConfiguration()
        );

        $this->assertEquals('theValue set via Constructor', ObjectAccess::getProperty($convertedObject, 'propertyMeantForConstructorUsage', true));
        $this->assertEquals('theValue set via Setter', ObjectAccess::getProperty($convertedObject, 'propertyMeantForSetterUsage', true));
        $this->assertEquals('theValue', ObjectAccess::getProperty($convertedObject, 'propertyMeantForPublicUsage', true));
    }
}
