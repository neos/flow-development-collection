<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Testcase for the Schema Generator
 */
final class SchemaGeneratorTest extends TestCase
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
     * @return \Iterator<(int | string), mixed>
     */
    public static function schemaGenerationForSimpleTypesDataProvider(): \Iterator
    {
        yield ['string', ['type' => 'string']];
        yield [false, ['type' => 'boolean']];
        yield [true, ['type' => 'boolean']];
        yield [10.75, ['type' => 'number']];
        yield [1234, ['type' => 'integer']];
        yield [null, ['type' => 'null']];
    }

    #[DataProvider('schemaGenerationForSimpleTypesDataProvider')]
    #[Test]
    public function testSchemaGenerationForSimpleTypes($value, array $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        self::assertEquals($schema, $expectedSchema);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function schemaGenerationForArrayOfTypesDataProvider(): \Iterator
    {
        yield [['string'], ['type' => 'array', 'items' => ['type' => 'string']]];
        yield [['string', 'foo', 'bar'], ['type' => 'array', 'items' => ['type' => 'string']]];
        yield [['string', 'foo', 123],  ['type' => 'array', 'items' => [['type' => 'string'], ['type' => 'integer']]]];
    }

    #[DataProvider('schemaGenerationForArrayOfTypesDataProvider')]
    #[Test]
    public function testSchemaGenerationForArrayOfTypes(array $value, array $expectedSchema)
    {
        $schema = $this->configurationGenerator->generate($value);
        self::assertEquals($schema, $expectedSchema);
    }
}
