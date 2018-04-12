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

use Neos\Flow\Property\TypeConverter\IntegerConverter;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Error\Messages as FlowError;

/**
 * Testcase for the Integer converter
 *
 * @covers \Neos\Flow\Property\TypeConverter\IntegerConverter<extended>
 */
class IntegerConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    public function setUp()
    {
        $this->converter = new IntegerConverter();
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        $this->assertEquals(['integer', 'string', 'DateTime'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        $this->assertEquals('integer', $this->converter->getSupportedTargetType(), 'Target type does not match');
        $this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @test
     */
    public function convertFromCastsStringToInteger()
    {
        $this->assertSame(15, $this->converter->convertFrom('15', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromCastsDateTimeToInteger()
    {
        $dateTime = new \DateTime();
        $this->assertSame($dateTime->format('U'), $this->converter->convertFrom($dateTime, 'integer'));
    }

    /**
     * @test
     */
    public function convertFromDoesNotModifyIntegers()
    {
        $source = 123;
        $this->assertSame($source, $this->converter->convertFrom($source, 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsNullIfEmptyStringSpecified()
    {
        $this->assertNull($this->converter->convertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric()
    {
        $this->assertInstanceOf(FlowError\Error::class, $this->converter->convertFrom('not numeric', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForANumericStringSource()
    {
        $this->assertTrue($this->converter->canConvertFrom('15', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForAnIntegerSource()
    {
        $this->assertTrue($this->converter->canConvertFrom(123, 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForAnEmptyValue()
    {
        $this->assertTrue($this->converter->canConvertFrom('', 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForANullValue()
    {
        $this->assertTrue($this->converter->canConvertFrom(null, 'integer'));
    }

    /**
     * @test
     */
    public function canConvertFromShouldReturnTrueForADateTimeValue()
    {
        $this->assertTrue($this->converter->canConvertFrom(new \DateTime(), 'integer'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        $this->assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
