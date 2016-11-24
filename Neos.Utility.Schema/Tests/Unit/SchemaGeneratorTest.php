<?php
namespace Neos\Flow\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Utility.Schema package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\SchemaGenerator;

/**
 * Testcase for the Schema Generator
 */
class SchemaGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemaGenerator
     */
    private $configurationGenerator;

    public function setUp()
    {
        $this->configurationGenerator = new SchemaGenerator();
    }

    /**
     * @return array
     */
    public function schemaGenerationForSimpleTypesDataProvider()
    {
        return [
            ['string', ['type' => 'string']],
            [false, ['type' => 'boolean']],
            [true, ['type' => 'boolean']],
            [10.75, ['type' => 'number']],
            [1234, ['type' => 'integer']],
            [null, ['type' => 'null']]
        ];
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
        return [
            [['string'], ['type' => 'array', 'items' => ['type' => 'string']]],
            [['string', 'foo', 'bar'], ['type' => 'array', 'items' => ['type' => 'string']]],
            [['string', 'foo', 123],  ['type' => 'array', 'items' => [['type' => 'string'], ['type' => 'integer']]]]
        ];
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
