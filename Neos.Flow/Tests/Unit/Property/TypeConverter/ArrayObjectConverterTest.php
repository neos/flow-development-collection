<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\ArrayConverter;
use Neos\Flow\Property\TypeConverter\ArrayObjectConverter;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ArrayObject converter
 */
class ArrayObjectConverterTest extends UnitTestCase
{
    /**
     * @var ArrayConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new ArrayObjectConverter();
    }

    /**
     * @test
     */
    public function checkMetadata(): void
    {
        self::assertEquals([\ArrayObject::class], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    public function arrayObjectDataProvider(): array
    {
        return [
            [new \ArrayObject(['Foo', 1, true, 'Bar']), ['Foo', 1, true, 'Bar']],
            [new \ArrayObject(), []]
        ];
    }

    /**
     * @test
     * @dataProvider arrayObjectDataProvider
     */
    public function canConvertToArray(\ArrayObject $source, array $expectedResult): void
    {
        $propertyMappingConfiguration = $this->createMock(PropertyMappingConfiguration::class);
        self::assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', [], $propertyMappingConfiguration));
    }
}
