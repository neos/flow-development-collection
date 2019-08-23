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

require_once(__DIR__ . '/../../Fixtures/ClassWithStringConstructor.php');
require_once(__DIR__ . '/../../Fixtures/ClassWithIntegerConstructor.php');
require_once(__DIR__ . '/../../Fixtures/ClassWithBoolConstructor.php');

use Neos\Flow\Fixtures\ClassWithBoolConstructor;
use Neos\Flow\Fixtures\ClassWithIntegerConstructor;
use Neos\Flow\Fixtures\ClassWithStringConstructor;
use Neos\Flow\Property\TypeConverter\ScalarTypeToObjectConverter;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;

/**
 * Test case for the ScalarTypeToObjectConverter
 *
 * @covers \Neos\Flow\Property\TypeConverter\ScalarTypeToObjectConverter<extended>
 */
class ScalarTypeToObjectConverterTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $reflectionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reflectionMock = $this->createMock(ReflectionService::class);
        $this->reflectionMock->expects(self::any())
            ->method('isClassAnnotatedWith')
            ->willReturn(false);
    }

    /**
     * @test
     */
    public function convertFromStringToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();
        $valueObject = $converter->convertFrom('Hello World!', ClassWithStringConstructor::class);
        self::assertEquals('Hello World!', $valueObject->value);
    }

    /**
     * @test
     */
    public function convertFromIntegerToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();
        $valueObject = $converter->convertFrom(42, ClassWithIntegerConstructor::class);
        self::assertSame(42, $valueObject->value);
    }

    /**
     * @test
     */
    public function convertFromBoolToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();
        $valueObject = $converter->convertFrom(true, ClassWithBoolConstructor::class);
        self::assertSame(true, $valueObject->value);
    }

    /**
     * @test
     */
    public function canConvertFromBoolToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();

        $this->reflectionMock->expects(self::once())
            ->method('getMethodParameters')
            ->willReturn([[
                'type' => 'bool'
            ]]);
        $this->inject($converter, 'reflectionService', $this->reflectionMock);
        $canConvert = $converter->canConvertFrom(true, ClassWithBoolConstructor::class);
        self::assertTrue($canConvert);
    }

    /**
     * @test
     */
    public function canConvertFromIntegerToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();

        $this->reflectionMock->expects(self::once())
            ->method('getMethodParameters')
            ->willReturn([[
                'type' => 'int'
            ]]);
        $this->inject($converter, 'reflectionService', $this->reflectionMock);
        $canConvert = $converter->canConvertFrom(42, ClassWithIntegerConstructor::class);
        self::assertTrue($canConvert);
    }
}
