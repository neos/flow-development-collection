<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\CollectionConverter;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Collection converter
 */
class CollectionConverterTest extends UnitTestCase
{
    /**
     * @var CollectionConverter
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new CollectionConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['string', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('Doctrine\Common\Collections\Collection', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsElementTypeFromTargetTypeIfGiven()
    {
        $this->assertEquals('FooBar', $this->converter->getTypeOfChildProperty('array<FooBar>', '', $this->createMock(PropertyMappingConfigurationInterface::class)));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyReturnsEmptyStringForElementTypeIfNotGivenInTargetType()
    {
        $this->assertEquals('', $this->converter->getTypeOfChildProperty('array', '', $this->createMock(PropertyMappingConfigurationInterface::class)));
    }
}
