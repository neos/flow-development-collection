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
use Neos\Error\Messages as Error;
use Neos\Flow\Property\TypeConverter\FloatConverter;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Float converter
 */
#[CoversClass('\Neos\Flow\Property\TypeConverter\FloatConverter<extended>::class')]
final class FloatConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->converter = new FloatConverter();
    }

    #[Test]
    public function checkMetadata()
    {
        self::assertEquals(['float', 'integer', 'string'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('float', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    #[Test]
    public function convertFromShouldCastTheStringToFloat()
    {
        self::assertSame(1.5, $this->converter->convertFrom('1.5', 'float'));
    }

    #[Test]
    public function convertFromReturnsNullIfEmptyStringSpecified()
    {
        self::assertNull($this->converter->convertFrom('', 'float'));
    }

    #[Test]
    public function convertFromShouldAcceptIntegers()
    {
        self::assertSame((float)123, $this->converter->convertFrom(123, 'float'));
    }

    #[Test]
    public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric()
    {
        self::assertInstanceOf(Error\Error::class, $this->converter->convertFrom('not numeric', 'float'));
    }

    #[Test]
    public function canConvertFromShouldReturnTrue()
    {
        self::assertTrue($this->converter->canConvertFrom('1.5', 'float'));
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
    public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray()
    {
        self::assertEquals([], $this->converter->getSourceChildPropertiesToBeConverted('myString'));
    }
}
