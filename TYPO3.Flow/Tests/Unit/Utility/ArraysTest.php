<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Utility Array class
 *
 */
class ArraysTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnEmptyArray() {
		$this->assertFalse(\TYPO3\Flow\Utility\Arrays::containsMultipleTypes(array()), 'An empty array was seen as containing multiple types');
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithIntegers() {
		$this->assertFalse(\TYPO3\Flow\Utility\Arrays::containsMultipleTypes(array(1, 2, 3)), 'An array with only integers was seen as containing multiple types');
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithObjects() {
		$this->assertFalse(\TYPO3\Flow\Utility\Arrays::containsMultipleTypes(array(new \stdClass(), new \stdClass(), new \stdClass())), 'An array with only \stdClass was seen as containing multiple types');
	}

	/**
	 * @test
	 */
	public function containsMultipleTypesReturnsTrueOnMixedArray() {
		$this->assertTrue(\TYPO3\Flow\Utility\Arrays::containsMultipleTypes(array(1, 'string', 1.25, new \stdClass())), 'An array with mixed contents was not seen as containing multiple types');
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenSimplePath() {
		$array = array('Foo' => 'the value');
		$this->assertSame('the value', \TYPO3\Flow\Utility\Arrays::getValueByPath($array, array('Foo')));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPath() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertSame('the value', \TYPO3\Flow\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Baz', 2)));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPathIfPathIsString() {
		$path = 'Foo.Bar.Baz.2';
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$expectedResult = 'the value';
		$actualResult = \TYPO3\Flow\Utility\Arrays::getValueByPath($array, $path);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function getValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\Flow\Utility\Arrays::getValueByPath($array, NULL);
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsNullIfTheSegementsOfThePathDontExist() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertNULL(\TYPO3\Flow\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Bax', 2)));
	}

	/**
	 * @test
	 */
	public function getValueByPathReturnsNullIfThePathHasMoreSegmentsThanTheGivenArray() {
		$array = array('Foo' => array('Bar' => array('Baz' => 'the value')));
		$this->assertNULL(\TYPO3\Flow\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Baz', 'Bux')));
	}

	/**
	 * @test
	 */
	public function convertObjectToArrayConvertsNestedObjectsToArray() {
		$object = new \stdClass();
		$object->a = 'v';
		$object->b = new \stdClass();
		$object->b->c = 'w';
		$object->d = array('i' => 'foo', 'j' => 12, 'k' => TRUE, 'l' => new \stdClass());

		$array = \TYPO3\Flow\Utility\Arrays::convertObjectToArray($object);
		$expected = array(
			'a' => 'v',
			'b' => array(
				'c' => 'w'
			),
			'd' => array(
				'i' => 'foo',
				'j' => 12,
				'k' => TRUE,
				'l' => array()
			)
		);

		$this->assertEquals($expected, $array);
	}

	/**
	 * @test
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsArray() {
		$array = array();
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\Flow\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsString() {
		$array = array();
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\Flow\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function setValueByPathRecursivelyMergesAnArray() {
		$array = array('foo' => array('bar' => 'should be overriden'), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')), 'bar' => 'Baz');
		$actualValue = \TYPO3\Flow\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\Flow\Utility\Arrays::setValueByPath($array, NULL, 'Some Value');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArray() {
		$subject = 'foobar';
		\TYPO3\Flow\Utility\Arrays::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArrayAccess() {
		$subject = new \stdClass();
		\TYPO3\Flow\Utility\Arrays::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setValueByLeavesInputArrayUnchanged() {
		$subject = $subjectBackup = array('foo' => 'bar');
		\TYPO3\Flow\Utility\Arrays::setValueByPath($subject, 'foo', 'baz');
		$this->assertEquals($subject, $subjectBackup);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathDoesNotModifyAnArrayIfThePathWasNotFound() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'nonExistingKey');
		$expectedValue = $array;
		$actualValue = \TYPO3\Flow\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedKey() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');;
		$actualValue = \TYPO3\Flow\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedKeyIfPathIsString() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');;
		$actualValue = \TYPO3\Flow\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 */
	public function unsetValueByPathRemovesSpecifiedBranch() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo');
		$expectedValue = array('bar' => 'Baz');;
		$actualValue = \TYPO3\Flow\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function unsetValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\Flow\Utility\Arrays::unsetValueByPath($array, NULL);
	}

	/**
	 * @test
	 */
	public function removeEmptyElementsRecursivelyRemovesNullValues() {
		$array = array('EmptyElement' => NULL, 'Foo' => array('Bar' => array('Baz' => array('NotNull' => '', 'AnotherEmptyElement' => NULL))));
		$expectedResult = array('Foo' => array('Bar' => array('Baz' => array('NotNull' => ''))));
		$actualResult = \TYPO3\Flow\Utility\Arrays::removeEmptyElementsRecursively($array);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeEmptyElementsRecursivelyRemovesEmptySubArrays() {
		$array = array('EmptyElement' => array(), 'Foo' => array('Bar' => array('Baz' => array('AnotherEmptyElement' => NULL))), 'NotNull' => 123);
		$expectedResult = array('NotNull' => 123);
		$actualResult = \TYPO3\Flow\Utility\Arrays::removeEmptyElementsRecursively($array);
		$this->assertEquals($expectedResult, $actualResult);
	}

	public function arrayMergeRecursiveOverruleData() {
		return array(
			'simple usage' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => 'v2',
				),
				'inputArray2' => array(
					'k2' => 'v2a',
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => TRUE, // default
				'expected' => array(
					'k1' => 'v1',
					'k2' => 'v2a',
					'k3' => 'v3'
				)
			),

			'simple usage with recursion' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1',
						'k2.2' => 'v2.2'
					),
				),
				'inputArray2' => array(
					'k2' => array(
						'k2.2' => 'v2.2a',
						'k2.3' => 'v2.3'
					),
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => TRUE, // default
				'expected' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1',
						'k2.2' => 'v2.2a',
						'k2.3' => 'v2.3'
					),
					'k3' => 'v3'
				)
			),

			'simple type should override array (k2)' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1'
					),
				),
				'inputArray2' => array(
					'k2' => 'v2a',
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => TRUE, // default
				'expected' => array(
					'k1' => 'v1',
					'k2' => 'v2a',
					'k3' => 'v3'
				)
			),

			'null should override array (k2)' => array(
				'inputArray1' => array(
					'k1' => 'v1',
					'k2' => array(
						'k2.1' => 'v2.1'
					),
				),
				'inputArray2' => array(
					'k2' => NULL,
					'k3' => 'v3'
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => TRUE, // default
				'expected' => array(
					'k1' => 'v1',
					'k2' => NULL,
					'k3' => 'v3'
				)
			),

			'empty array should override array (k2)' => array(
				'inputArray1' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
				),
				'inputArray2' => array(
					'k2' => array(),
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => TRUE, // default
				'expected' => array(
					'k2' => array()
				)
			),

			'empty array without emptyValuesOverride should not override array (k2)' => array(
				'inputArray1' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
				),
				'inputArray2' => array(
					'k2' => array(),
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => FALSE,
				'expected' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					)
				)
			),

			'empty array without emptyValuesOverride should add new key (k3)' => array(
				'inputArray1' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
				),
				'inputArray2' => array(
					'k3' => array(),
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => FALSE,
				'expected' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
					'k3' => array()
				)
			),

			'empty array without emptyValuesOverride should not override existing key (k3)' => array(
				'inputArray1' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
					'k3' => 'v3'
				),
				'inputArray2' => array(
					'k3' => array(),
				),
				'dontAddNewKeys' => FALSE, // default
				'emptyValuesOverride' => FALSE,
				'expected' => array(
					'k2' => array(
						'k2.1' => 'v2.1'
					),
					'k3' => 'v3'
				)
			)
		);
	}

	/**
	 * @dataProvider arrayMergeRecursiveOverruleData
	 * @test
	 */
	public function arrayMergeRecursiveOverruleMergesSimpleArrays($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride, $expected) {
		$actual = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($inputArray1, $inputArray2, $dontAddNewKeys, $emptyValuesOverride);
		$this->assertSame($expected, $actual);
	}
}
