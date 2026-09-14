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
use Neos\Flow\Property\TypeConverter\DenormalizingObjectConverter;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\ArrayBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\BooleanBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\BooleanBasedValueObjectWithLongName;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\FloatBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\IntegerBasedValueObject;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\IntegerBasedValueObjectWithLongName;
use Neos\Flow\Tests\Unit\Property\TypeConverter\Fixture\StringBasedValueObject;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

require_once "Fixture/ArrayBasedValueObject.php";
require_once "Fixture/BooleanBasedValueObject.php";
require_once "Fixture/BooleanBasedValueObjectWithLongName.php";
require_once "Fixture/ExampleIntBackedEnum.php";
require_once "Fixture/ExampleStringBackedEnum.php";
require_once "Fixture/FloatBasedValueObject.php";
require_once "Fixture/IntegerBasedValueObject.php";
require_once "Fixture/IntegerBasedValueObjectWithLongName.php";
require_once "Fixture/StringBasedValueObject.php";

final class DenormalizingObjectConverterTest extends UnitTestCase
{
    /**
     * @return void
     */
    #[Test]
    public function identifiesDenormalizableClasses(): void
    {
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(ArrayBasedValueObject::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(StringBasedValueObject::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(BooleanBasedValueObject::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(BooleanBasedValueObjectWithLongName::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(IntegerBasedValueObject::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(IntegerBasedValueObjectWithLongName::class));
        $this->assertTrue(DenormalizingObjectConverter::isDenormalizable(FloatBasedValueObject::class));

        $this->assertFalse(DenormalizingObjectConverter::isDenormalizable(UnitTestCase::class));
        $this->assertFalse(DenormalizingObjectConverter::isDenormalizable(DenormalizingObjectConverter::class));
        $this->assertFalse(DenormalizingObjectConverter::isDenormalizable(\stdClass::class));
        $this->assertFalse(DenormalizingObjectConverter::isDenormalizable(\DateTimeInterface::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromArray(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(['key' => 'value'], ArrayBasedValueObject::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromArray(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $result = $typeConverter->convertFrom(['key' => 'value'], ArrayBasedValueObject::class);

        $this->assertInstanceOf(ArrayBasedValueObject::class, $result);
        $this->assertEquals(['key' => 'value'], $result->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromString(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom('string', StringBasedValueObject::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromString(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $result = $typeConverter->convertFrom('string', StringBasedValueObject::class);

        $this->assertInstanceOf(StringBasedValueObject::class, $result);
        $this->assertEquals('string', $result->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromBoolean(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(true, BooleanBasedValueObject::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromBoolean(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $resultFalse = $typeConverter->convertFrom(false, BooleanBasedValueObject::class);

        $this->assertInstanceOf(BooleanBasedValueObject::class, $resultFalse);
        $this->assertEquals(false, $resultFalse->getValue());

        $resultTrue = $typeConverter->convertFrom(true, BooleanBasedValueObject::class);

        $this->assertInstanceOf(BooleanBasedValueObject::class, $resultTrue);
        $this->assertEquals(true, $resultTrue->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromBooleanWithLongName(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(true, BooleanBasedValueObjectWithLongName::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromBooleanWithLongName(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $resultFalse = $typeConverter->convertFrom(false, BooleanBasedValueObjectWithLongName::class);

        $this->assertInstanceOf(BooleanBasedValueObjectWithLongName::class, $resultFalse);
        $this->assertEquals(false, $resultFalse->getValue());

        $resultTrue = $typeConverter->convertFrom(true, BooleanBasedValueObjectWithLongName::class);

        $this->assertInstanceOf(BooleanBasedValueObjectWithLongName::class, $resultTrue);
        $this->assertEquals(true, $resultTrue->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromInteger(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(42, IntegerBasedValueObject::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromInteger(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $result = $typeConverter->convertFrom(12264, IntegerBasedValueObject::class);

        $this->assertInstanceOf(IntegerBasedValueObject::class, $result);
        $this->assertEquals(12264, $result->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromIntegerWithLongName(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(42, IntegerBasedValueObjectWithLongName::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromIntegerWithLongName(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $result = $typeConverter->convertFrom(12264, IntegerBasedValueObjectWithLongName::class);

        $this->assertInstanceOf(IntegerBasedValueObjectWithLongName::class, $result);
        $this->assertEquals(12264, $result->getValue());
    }

    /**
     * @return void
     */
    #[Test]
    public function canConvertFromFloat(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $this->assertTrue($typeConverter->canConvertFrom(23.3, FloatBasedValueObject::class));
    }

    /**
     * @return void
     */
    #[Test]
    public function convertsFromFloat(): void
    {
        $typeConverter = new DenormalizingObjectConverter();
        $result = $typeConverter->convertFrom(12264.123, FloatBasedValueObject::class);

        $this->assertInstanceOf(FloatBasedValueObject::class, $result);
        $this->assertEqualsWithDelta(12264.123, $result->getValue(), PHP_FLOAT_EPSILON);
    }
}
