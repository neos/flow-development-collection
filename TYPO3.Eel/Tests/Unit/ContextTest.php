<?php
namespace TYPO3\Eel\Tests\Unit;

/*
 * This file is part of the TYPO3.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\Context;

/**
 * Eel context test
 */
class ContextTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Data provider with simple values
     *
     * @return array
     */
    public function simpleValues()
    {
        return array(
            array('Test', 'Test'),
            array(true, true),
            array(42, 42),
            array(7.0, 7.0),
            array(null, null)
        );
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
        return array(
            array(array(), array()),
            array(array(1, 2, 3), array(1, 2, 3)),
            // Unwrap has to be recursive
            array(array(new Context('Foo')), array('Foo')),
            array(array('arr' => array(new Context('Foo'))), array('arr' => array('Foo')))
        );
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
        return array(
            array(array(), 'foo', null),
            array(array('foo' => 'bar'), 'foo', 'bar'),
            array(array(1, 2, 3), '1', 2),
            array(array('foo' => array('bar' => 'baz')), 'foo', array('bar' => 'baz')),
            array(new \ArrayObject(array('foo' => 'bar')), 'foo', 'bar')
        );
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
        $getterObject = new \TYPO3\Eel\Tests\Unit\Fixtures\TestObject();
        $getterObject->setProperty('some value');
        $getterObject->setBooleanProperty(true);

        return array(
            array($simpleObject, 'bar', null),
            array($simpleObject, 'foo', 'bar'),
            array($getterObject, 'foo', null),
            array($getterObject, 'callMe', null),
            array($getterObject, 'booleanProperty', true)
        );
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
