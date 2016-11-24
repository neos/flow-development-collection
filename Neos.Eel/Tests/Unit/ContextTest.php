<?php
namespace Neos\Eel\Tests\Unit;

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\Context;

/**
 * Eel context test
 */
class ContextTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * Data provider with simple values
     *
     * @return array
     */
    public function simpleValues()
    {
        return [
            ['Test', 'Test'],
            [true, true],
            [42, 42],
            [7.0, 7.0],
            [null, null]
        ];
    }

    /**
     * @test
     * @dataProvider simpleValues
     *
     * @param mixed $value
     * @param mixed $expectedUnwrappedValue
     */
    public function unwrapSimpleValues($value, $expectedUnwrappedValue)
    {
        $context = new Context($value);
        $unwrappedValue = $context->unwrap();
        $this->assertSame($expectedUnwrappedValue, $unwrappedValue);
    }

    /**
     * Data provider with array values
     *
     * @return array
     */
    public function arrayValues()
    {
        return [
            [[], []],
            [[1, 2, 3], [1, 2, 3]],
            // Unwrap has to be recursive
            [[new Context('Foo')], ['Foo']],
            [['arr' => [new Context('Foo')]], ['arr' => ['Foo']]]
        ];
    }

    /**
     * @test
     * @dataProvider arrayValues
     *
     * @param mixed $value
     * @param mixed $expectedUnwrappedValue
     */
    public function unwrapArrayValues($value, $expectedUnwrappedValue)
    {
        $context = new Context($value);
        $unwrappedValue = $context->unwrap();
        $this->assertSame($expectedUnwrappedValue, $unwrappedValue);
    }

    /**
     * Data provider with array values
     *
     * @return array
     */
    public function arrayGetValues()
    {
        return [
            [[], 'foo', null],
            [['foo' => 'bar'], 'foo', 'bar'],
            [[1, 2, 3], '1', 2],
            [['foo' => ['bar' => 'baz']], 'foo', ['bar' => 'baz']],
            [new \ArrayObject(['foo' => 'bar']), 'foo', 'bar']
        ];
    }

    /**
     * @test
     * @dataProvider arrayGetValues
     *
     * @param mixed $value
     * @param string $path
     * @param mixed $expectedGetValue
     */
    public function getValueByPathForArrayValues($value, $path, $expectedGetValue)
    {
        $context = new Context($value);
        $getValue = $context->get($path);
        $this->assertSame($getValue, $expectedGetValue);
    }

    /**
     * Data provider with object values
     *
     * @return array
     */
    public function objectGetValues()
    {
        $simpleObject = new \stdClass();
        $simpleObject->foo = 'bar';
        $getterObject = new \Neos\Eel\Tests\Unit\Fixtures\TestObject();
        $getterObject->setProperty('some value');
        $getterObject->setBooleanProperty(true);

        return [
            [$simpleObject, 'bar', null],
            [$simpleObject, 'foo', 'bar'],
            [$getterObject, 'foo', null],
            [$getterObject, 'callMe', null],
            [$getterObject, 'booleanProperty', true]
        ];
    }

    /**
     * @test
     * @dataProvider objectGetValues
     *
     * @param mixed $value
     * @param string $path
     * @param mixed $expectedGetValue
     */
    public function getValueByPathForObjectValues($value, $path, $expectedGetValue)
    {
        $context = new Context($value);
        $getValue = $context->get($path);
        $this->assertSame($getValue, $expectedGetValue);
    }
}
