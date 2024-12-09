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
class SchemaGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SchemaGenerator
     */
    private $configurationGenerator;

    protected function setUp(): void
    {
        $this->configurationGenerator = new SchemaGenerator();
    }

    /**
     * @return array
     */
    public static function schemaGenerationForSimpleTypesDataProvider(): array
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
     */
    public function testSchemaGenerationForSimpleTypes($value, array $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        self::assertEquals($schema, $expectedSchema);
    }

    /**
     * @return array
     */
    public static function schemaGenerationForArrayOfTypesDataProvider(): array
    {
        return [
            [['string'], ['type' => 'array', 'items' => ['type' => 'string']]],
            [['string', 'foo', 'bar'], ['type' => 'array', 'items' => ['type' => 'string']]],
            [['string', 'foo', 123],  ['type' => 'array', 'items' => [['type' => 'string'], ['type' => 'integer']]]]
        ];
    }

    /**
     * @dataProvider schemaGenerationForArrayOfTypesDataProvider
     */
    public function testSchemaGenerationForArrayOfTypes(array $value, array $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        self::assertEquals($schema, $expectedSchema);
    }
}
