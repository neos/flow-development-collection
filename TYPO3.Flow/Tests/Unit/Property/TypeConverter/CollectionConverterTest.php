<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Collection converter
 *
 */
class CollectionConverterTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Property\TypeConverter\CollectionConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new \TYPO3\Flow\Property\TypeConverter\CollectionConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('Doctrine\Common\Collections\Collection', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsElementTypeFromTargetTypeIfGiven()
    {
        $this->assertEquals('FooBar', $this->converter->getTypeOfChildProperty('array<FooBar>', '', $this->getMock(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface::class)));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsEmptyStringForElementTypeIfNotGivenInTargetType()
    {
        $this->assertEquals('', $this->converter->getTypeOfChildProperty('array', '', $this->getMock(\TYPO3\Flow\Property\PropertyMappingConfigurationInterface::class)));
    }
}
