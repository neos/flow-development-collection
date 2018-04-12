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

use Neos\Eel\Helper\JsonHelper;

/**
 * Tests for JsonHelper
 */
class JsonHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function stringifyExamples()
    {
        return [
            'string value' => [
                'Foo', '"Foo"'
            ],
            'null value' => [
                null, 'null'
            ],
            'numeric value' => [
                42, '42'
            ],
            'array value' => [
                ['Foo', 'Bar'], '["Foo","Bar"]'
            ]
        ];
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
        return [
            'string value' => [
                ['"Foo"'], 'Foo'
            ],
            'null value' => [
                ['null'], null
            ],
            'numeric value' => [
                ['42'], 42
            ],
            'array value' => [
                ['["Foo","Bar"]'], ['Foo', 'Bar']
            ],
            'object value is parsed as associative array by default' => [
                ['{"name":"Foo"}'], ['name' => 'Foo']
            ],
            'object value without associative array' => [
                ['{"name":"Foo"}', false], (object)['name' => 'Foo']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider parseExamples
     */
    public function parseWorks($arguments, $expected)
    {
        $helper = new JsonHelper();
        $result = call_user_func_array([$helper, 'parse'], $arguments);
        $this->assertEquals($expected, $result);
    }
}
