<?php
namespace TYPO3\Eel\Tests\Unit;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Eel\Helper\JsonHelper;

/**
 * Tests for JsonHelper
 */
class JsonHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    public function stringifyExamples()
    {
        return array(
            'string value' => array(
                'Foo', '"Foo"'
            ),
            'null value' => array(
                null, 'null'
            ),
            'numeric value' => array(
                42, '42'
            ),
            'array value' => array(
                array('Foo', 'Bar'), '["Foo","Bar"]'
            )
        );
    }

    /**
     * @test
     * @dataProvider stringifyExamples
     */
    public function stringifyWorks($value, $expected)
    {
        $helper = new JsonHelper();
        $result = $helper->stringify($value);
        $this->assertEquals($expected, $result);
    }

    public function parseExamples()
    {
        return array(
            'string value' => array(
                array('"Foo"'), 'Foo'
            ),
            'null value' => array(
                array('null'), null
            ),
            'numeric value' => array(
                array('42'), 42
            ),
            'array value' => array(
                array('["Foo","Bar"]'), array('Foo', 'Bar')
            ),
            'object value is parsed as associative array by default' => array(
                array('{"name":"Foo"}'), array('name' => 'Foo')
            ),
            'object value without associative array' => array(
                array('{"name":"Foo"}', false), (object)array('name' => 'Foo')
            )
        );
    }

    /**
     * @test
     * @dataProvider parseExamples
     */
    public function parseWorks($arguments, $expected)
    {
        $helper = new JsonHelper();
        $result = call_user_func_array(array($helper, 'parse'), $arguments);
        $this->assertEquals($expected, $result);
    }
}
