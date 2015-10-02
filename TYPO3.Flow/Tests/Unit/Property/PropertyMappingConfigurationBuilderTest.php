<?php
namespace TYPO3\Flow\Tests\Unit\Property;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @covers \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
 */
class PropertyMappingConfigurationBuilderTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     *
     * @var \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
     */
    protected $propertyMappingConfigurationBuilder;

    public function setUp()
    {
        $this->propertyMappingConfigurationBuilder = new \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder();
    }

    /**
     * @test
     */
    public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration()
    {
        $defaultConfiguration = $this->propertyMappingConfigurationBuilder->build();
        $this->assertTrue($defaultConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertTrue($defaultConfiguration->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

        $this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
        $this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', \TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
    }
}
