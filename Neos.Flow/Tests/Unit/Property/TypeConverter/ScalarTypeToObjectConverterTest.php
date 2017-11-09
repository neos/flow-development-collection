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

use Neos\Flow\Fixtures\ClassWithIntegerConstructor;
use Neos\Flow\Fixtures\ClassWithStringConstructor;
use Neos\Flow\Property\TypeConverter\ScalarTypeToObjectConverter;
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
     * @test
     */
    public function convertFromStringToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();
        $valueObject = $converter->convertFrom('Hello World!', ClassWithStringConstructor::class);
        $this->assertEquals('Hello World!', $valueObject->value);
    }

    /**
     * @test
     */
    public function convertFromIntegerToValueObject()
    {
        $converter = new ScalarTypeToObjectConverter();
        $valueObject = $converter->convertFrom(42, ClassWithIntegerConstructor::class);
        $this->assertSame(42, $valueObject->value);
    }
}
