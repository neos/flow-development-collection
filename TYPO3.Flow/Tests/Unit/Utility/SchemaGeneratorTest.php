<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the Schema Generator
 *
 */
class SchemaGeneratorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Utility\SchemaGenerator
     */
    private $configurationGenerator;

    public function setUp()
    {
        $this->configurationGenerator = $this->getAccessibleMock(\TYPO3\Flow\Utility\SchemaGenerator::class, array('getError'));
    }

    /**
     * @return array
     */
    public function schemaGenerationForSimpleTypesDataProvider()
    {
        return array(
            array('string', array('type' => 'string')),
            array(false, array('type' => 'boolean')),
            array(true, array('type' => 'boolean')),
            array(10.75, array('type' => 'number')),
            array(1234, array('type' => 'integer')),
            array(null, array('type' => 'null'))
        );
    }

    /**
     * @dataProvider schemaGenerationForSimpleTypesDataProvider
     * @test
     */
    public function testSchemaGenerationForSimpleTypes($value, $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        $this->assertEquals($schema, $expectedSchema);
    }

    /**
     * @return array
     */
    public function schemaGenerationForArrayOfTypesDataProvider()
    {
        return array(
            array(array('string'), array('type' => 'array', 'items' => array('type' => 'string'))),
            array(array('string', 'foo', 'bar'), array('type' => 'array', 'items' => array('type' => 'string'))),
            array(array('string', 'foo', 123),  array('type' => 'array', 'items' => array(array('type' => 'string'), array('type' => 'integer'))))
        );
    }

    /**
     * @dataProvider schemaGenerationForArrayOfTypesDataProvider
     * @test
     */
    public function testSchemaGenerationForArrayOfTypes($value, $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        $this->assertEquals($schema, $expectedSchema);
    }
}
