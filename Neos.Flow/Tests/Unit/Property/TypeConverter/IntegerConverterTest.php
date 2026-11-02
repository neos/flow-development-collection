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
use Neos\Error\Messages as FlowError;
use Neos\Flow\Property\TypeConverter\IntegerConverter;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Integer converter
 */
#[CoversClass('\Neos\Flow\Property\TypeConverter\IntegerConverter<extended>::class')]
final class IntegerConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new IntegerConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['integer', 'string', 'DateTime'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('integer', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromCastsStringToInteger()
    {
        self::assertSame(15, $this->converter->convertFrom('15', 'integer'));
    }

    #[Test]
    public function convertFromCastsDateTimeToInteger()
    {
        $dateTime = new \DateTime();
        self::assertSame((int)$dateTime->format('U'), $this->converter->convertFrom($dateTime, 'integer'));
    }

    #[Test]
    public function convertFromDoesNotModifyIntegers()
    {
        $source = 123;
        self::assertSame($source, $this->converter->convertFrom($source, 'integer'));
    }

    #[Test]
    public function convertFromReturnsNullIfEmptyStringSpecified()
    {
        self::assertNull($this->converter->convertFrom('', 'integer'));
    }

    #[Test]
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric()
    {
        self::assertInstanceOf(FlowError\Error::class, $this->converter->convertFrom('not numeric', 'integer'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrueForANumericStringSource()
    {
        self::assertTrue($this->converter->canConvertFrom('15', 'integer'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrueForAnIntegerSource()
    {
        self::assertTrue($this->converter->canConvertFrom(123, 'integer'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrueForAnEmptyValue()
    {
        self::assertTrue($this->converter->canConvertFrom('', 'integer'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrueForANullValue()
    {
        self::assertTrue($this->converter->canConvertFrom(null, 'integer'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrueForADateTimeValue()
    {
        self::assertTrue($this->converter->canConvertFrom(new \DateTime(), 'integer'));
    }

    #[Test]
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
