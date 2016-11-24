<?php
namespace Neos\Utility\Arrays\Tests\Unit;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;

/**
 * Testcase for the Utility Array class
 */
class ArraysTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnEmptyArray()
    {
        $this->assertFalse(Arrays::containsMultipleTypes([]), 'An empty array was seen as containing multiple types');
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnArrayWithIntegers()
    {
        $this->assertFalse(Arrays::containsMultipleTypes([1, 2, 3]), 'An array with only integers was seen as containing multiple types');
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsFalseOnArrayWithObjects()
    {
        $this->assertFalse(Arrays::containsMultipleTypes([new \stdClass(), new \stdClass(), new \stdClass()]), 'An array with only \stdClass was seen as containing multiple types');
    }

    /**
     * @test
     */
    public function containsMultipleTypesReturnsTrueOnMixedArray()
    {
        $this->assertTrue(Arrays::containsMultipleTypes([1, 'string', 1.25, new \stdClass()]), 'An array with mixed contents was not seen as containing multiple types');
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenSimplePath()
    {
        $array = ['Foo' => 'the value'];
        $this->assertSame('the value', Arrays::getValueByPath($array, ['Foo']));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPath()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $this->assertSame('the value', Arrays::getValueByPath($array, ['Foo', 'Bar', 'Baz', 2]));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPathIfPathIsString()
    {
        $path = 'Foo.Bar.Baz.2';
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $expectedResult = 'the value';
        $actualResult = Arrays::getValueByPath($array, $path);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function getValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        Arrays::getValueByPath($array, null);
    }

    /**
     * @test
     */
    public function getValueByPathReturnsNullIfTheSegementsOfThePathDontExist()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        $this->assertNULL(Arrays::getValueByPath($array, ['Foo', 'Bar', 'Bax', 2]));
    }

    /**
     * @test
     */
    public function getValueByPathReturnsNullIfThePathHasMoreSegmentsThanTheGivenArray()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => 'the value']]];
        $this->assertNULL(Arrays::getValueByPath($array, ['Foo', 'Bar', 'Baz', 'Bux']));
    }

    /**
     * @test
     */
    public function convertObjectToArrayConvertsNestedObjectsToArray()
    {
        $object = new \stdClass();
        $object->a = 'v';
        $object->b = new \stdClass();
        $object->b->c = 'w';
        $object->d = ['i' => 'foo', 'j' => 12, 'k' => true, 'l' => new \stdClass()];

        $array = Arrays::convertObjectToArray($object);
        $expected = [
            'a' => 'v',
            'b' => [
                'c' => 'w'
            ],
            'd' => [
                'i' => 'foo',
                'j' => 12,
                'k' => true,
                'l' => []
            ]
        ];

        $this->assertEquals($expected, $array);
    }

    /**
     * @test
     */
    public function setValueByPathSetsValueRecursivelyIfPathIsArray()
    {
        $array = [];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']]];
        $actualValue = Arrays::setValueByPath($array, $path, 'The Value');
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function setValueByPathSetsValueRecursivelyIfPathIsString()
    {
        $array = [];
        $path = 'foo.bar.baz';
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']]];
        $actualValue = Arrays::setValueByPath($array, $path, 'The Value');
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function setValueByPathRecursivelyMergesAnArray()
    {
        $array = ['foo' => ['bar' => 'should be overriden'], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => ['baz' => 'The Value']], 'bar' => 'Baz'];
        $actualValue = Arrays::setValueByPath($array, $path, 'The Value');
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        Arrays::setValueByPath($array, null, 'Some Value');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfSubjectIsNoArray()
    {
        $subject = 'foobar';
        Arrays::setValueByPath($subject, 'foo', 'bar');
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setValueByPathThrowsExceptionIfSubjectIsNoArrayAccess()
    {
        $subject = new \stdClass();
        Arrays::setValueByPath($subject, 'foo', 'bar');
    }

    /**
     * @test
     */
    public function setValueByLeavesInputArrayUnchanged()
    {
        $subject = $subjectBackup = ['foo' => 'bar'];
        Arrays::setValueByPath($subject, 'foo', 'baz');
        $this->assertEquals($subject, $subjectBackup);
    }

    /**
     * @test
     */
    public function unsetValueByPathDoesNotModifyAnArrayIfThePathWasNotFound()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'nonExistingKey'];
        $expectedValue = $array;
        $actualValue = Arrays::unsetValueByPath($array, $path);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedKey()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo', 'bar', 'baz'];
        $expectedValue = ['foo' => ['bar' => []], 'bar' => 'Baz'];
        ;
        $actualValue = Arrays::unsetValueByPath($array, $path);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedKeyIfPathIsString()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = 'foo.bar.baz';
        $expectedValue = ['foo' => ['bar' => []], 'bar' => 'Baz'];
        ;
        $actualValue = Arrays::unsetValueByPath($array, $path);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     */
    public function unsetValueByPathRemovesSpecifiedBranch()
    {
        $array = ['foo' => ['bar' => ['baz' => 'Some Value']], 'bar' => 'Baz'];
        $path = ['foo'];
        $expectedValue = ['bar' => 'Baz'];
        ;
        $actualValue = Arrays::unsetValueByPath($array, $path);
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function unsetValueByPathThrowsExceptionIfPathIsNoArrayOrString()
    {
        $array = ['Foo' => ['Bar' => ['Baz' => [2 => 'the value']]]];
        Arrays::unsetValueByPath($array, null);
    }

    /**
     * @test
     */
    public function removeEmptyElementsRecursivelyRemovesNullValues()
    {
        $array = ['EmptyElement' => null, 'Foo' => ['Bar' => ['Baz' => ['NotNull' => '', 'AnotherEmptyElement' => null]]]];
        $expectedResult = ['Foo' => ['Bar' => ['Baz' => ['NotNull' => '']]]];
        $actualResult = Arrays::removeEmptyElementsRecursively($array);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function removeEmptyElementsRecursivelyRemovesEmptySubArrays()
    {
        $array = ['EmptyElement' => [], 'Foo' => ['Bar' => ['Baz' => ['AnotherEmptyElement' => null]]], 'NotNull' => 123];
        $expectedResult = ['NotNull' => 123];
        $actualResult = Arrays::removeEmptyElementsRecursively($array);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function arrayMergeRecursiveOverruleData()
    {
        return [
            'simple usage' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => 'v2',
                ],
                'inputArray2' => [
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ]
            ],

            'simple usage with recursion' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2'
                    ],
                ],
                'inputArray2' => [
                    'k2' => [
                        'k2.2' => 'v2.2a',
                        'k2.3' => 'v2.3'
                    ],
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2a',
                        'k2.3' => 'v2.3'
                    ],
                    'k3' => 'v3'
                ]
            ],

            'nested array with recursion' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2',
                        'k2.4' => [
                            'k2.4.1' => 'v2.4.1'
                        ]
                    ],
                ],
                'inputArray2' => [
                    'k2' => [
                        'k2.2' => 'v2.2a',
                        'k2.3' => 'v2.3',
                        'k2.4' => [
                            'k2.4.2' => 'v2.4.2'
                        ]
                    ],
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1',
                        'k2.2' => 'v2.2a',
                        'k2.4' => [
                            'k2.4.1' => 'v2.4.1',
                            'k2.4.2' => 'v2.4.2'
                        ],
                        'k2.3' => 'v2.3'
                    ],
                    'k3' => 'v3'
                ]
            ],

            'simple type should override array (k2)' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                ],
                'inputArray2' => [
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => 'v2a',
                    'k3' => 'v3'
                ]
            ],

            'null should override array (k2)' => [
                'inputArray1' => [
                    'k1' => 'v1',
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                ],
                'inputArray2' => [
                    'k2' => null,
                    'k3' => 'v3'
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k1' => 'v1',
                    'k2' => null,
                    'k3' => 'v3'
                ]
            ],

            'empty array should override array (k2)' => [
                'inputArray1' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                ],
                'inputArray2' => [
                    'k2' => [],
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => true, // default
                'expected' => [
                    'k2' => []
                ]
            ],

            'empty array without emptyValuesOverride should not override array (k2)' => [
                'inputArray1' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                ],
                'inputArray2' => [
                    'k2' => [],
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => false,
                'expected' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ]
                ]
            ],

            'empty array without emptyValuesOverride should add new key (k3)' => [
                'inputArray1' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                ],
                'inputArray2' => [
                    'k3' => [],
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => false,
                'expected' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                    'k3' => []
                ]
            ],

            'empty array without emptyValuesOverride should not override existing key (k3)' => [
                'inputArray1' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                    'k3' => 'v3'
                ],
                'inputArray2' => [
                    'k3' => [],
                ],
                'dontAddNewKeys' => false, // default
                'emptyValuesOverride' => false,
                'expected' => [
                    'k2' => [
                        'k2.1' => 'v2.1'
                    ],
                    'k3' => 'v3'
                ]
            ]
        ];
    }

    /**
     * @dataProvider arrayMergeRecursiveOverruleData
     * @test
     */
    public function arrayMergeRecursiveOverruleMergesSimpleArrays($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride, $expected)
    {
        $actual = Arrays::arrayMergeRecursiveOverrule($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride);
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function arrayMergeRecursiveCallbackConvertsSimpleValuesWithGivenClosure()
    {
        $inputArray1 = [
            'k1' => 'v1',
            'k2' => [
                'k2.1' => 'v2.1'
            ],
        ];
        $inputArray2 = [
            'k2' => 'v2.2',
            'k3' => 'v3'
        ];
        $expected = [
            'k1' => 'v1',
            'k2' => [
                'k2.1' => 'v2.1',
                '__convertedValue' => 'v2.2'
            ],
            'k3' => 'v3'
        ];

        $actual = Arrays::arrayMergeRecursiveOverruleWithCallback($inputArray1, $inputArray2, function ($simpleType) {
            return ['__convertedValue' => $simpleType];
        });
        $this->assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function arrayMergeRecursiveCallbackConvertsSimpleValuesWithGivenClosureAndReturnedSimpleTypesOverwrite()
    {
        $inputArray1 = [
            'k1' => 'v1',
            'k2' => [
                'k2.1' => 'v2.1'
            ],
            'k3' => [
                'k3.1' => 'value'
            ]
        ];
        $inputArray2 = [
            'k2' => 'v2.2',
            'k3' => null
        ];
        $expected = [
            'k1' => 'v1',
            'k2' => [
                'k2.1' => 'v2.1',
                '__convertedValue' => 'v2.2'
            ],
            'k3' => null
        ];

        $actual = Arrays::arrayMergeRecursiveOverruleWithCallback($inputArray1, $inputArray2, function ($simpleType) {
            if ($simpleType === null) {
                return null;
            }
            return ['__convertedValue' => $simpleType];
        });
        $this->assertSame($expected, $actual);
    }
}
