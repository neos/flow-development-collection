<?php
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

/**
 * Testcase for the Boolean converter
 */
class BooleanConverterTest extends UnitTestCase
{
    /**
     * @var BooleanConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new BooleanConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['boolean', 'string', 'integer', 'float'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyTheBooleanSource()
    {
        $source = true;
        self::assertSame($source, $this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsSourceStringToBoolean()
    {
        $source = 'true';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    /**
     * @test
     */
    public function convertFromCastsNumericSourceStringToBoolean()
    {
        $source = '1';
        self::assertTrue($this->converter->convertFrom($source, 'boolean'));
    }

    public function convertFromDataProvider()
    {
        return [
            ['', false],
            ['0', false],
            ['1', true],
            ['false', false],
            ['true', true],
            ['some string', true],
            ['FaLsE', false],
            ['tRuE', true],
            ['tRuE', true],
            ['off', false],
            ['N', false],
            ['no', false],
            ['not no', true],
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            [1.0, true],
        ];
    }

    /**
     * @test
     * @param mixed $source
     * @param boolean $expected
     * @dataProvider convertFromDataProvider
     */
    public function convertFromTests($source, $expected)
    {
        self::assertSame($expected, $this->converter->convertFrom($source, 'boolean'));
    }
}
