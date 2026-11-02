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
use Neos\Flow\Property\TypeConverter\BooleanConverter;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Boolean converter
 */
final class BooleanConverterTest extends UnitTestCase
{
    /**
     * @var BooleanConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new BooleanConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['boolean', 'string', 'integer', 'float'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromDoesNotModifyTheBooleanSource()
    {
        $source = true;
        self::assertSame($source, $this->converter->convertFrom($source, 'boolean'));
    }

    #[Test]
    public function convertFromCastsSourceStringToBoolean()
    {
        $source = 'true';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    #[Test]
    public function convertFromCastsNumericSourceStringToBoolean()
    {
        $source = '1';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    public static function convertFromDataProvider(): \Iterator
    {
        yield ['', false];
        yield ['0', false];
        yield ['1', true];
        yield ['false', false];
        yield ['true', true];
        yield ['some string', true];
        yield ['FaLsE', false];
        yield ['tRuE', true];
        yield ['tRuE', true];
        yield ['off', false];
        yield ['N', false];
        yield ['no', false];
        yield ['not no', true];
        yield [true, true];
        yield [false, false];
        yield [1, true];
        yield [0, false];
        yield [1.0, true];
    }

    /**
     * @param mixed $source
     * @param boolean $expected
     */
    #[DataProvider('convertFromDataProvider')]
    #[Test]
    public function convertFromTests($source, $expected)
    {
        self::assertSame($expected, $this->converter->convertFrom($source, 'boolean'));
    }
}
