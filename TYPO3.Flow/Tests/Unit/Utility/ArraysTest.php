<?php
namespace TYPO3\FLOW3\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Utility Array class
 *
 */
class ArraysTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnEmptyArray() {
		$this->assertFalse(\TYPO3\FLOW3\Utility\Arrays::containsMultipleTypes(array()), 'An empty array was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithIntegers() {
		$this->assertFalse(\TYPO3\FLOW3\Utility\Arrays::containsMultipleTypes(array(1, 2, 3)), 'An array with only integers was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithObjects() {
		$this->assertFalse(\TYPO3\FLOW3\Utility\Arrays::containsMultipleTypes(array(new \stdClass(), new \stdClass(), new \stdClass())), 'An array with only \stdClass was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsTrueOnMixedArray() {
		$this->assertTrue(\TYPO3\FLOW3\Utility\Arrays::containsMultipleTypes(array(1, 'string', 1.25, new \stdClass())), 'An array with mixed contents was not seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenSimplePath() {
		$array = array('Foo' => 'the value');
		$this->assertSame('the value', \TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, array('Foo')));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPath() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertSame('the value', \TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Baz', 2)));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getValueByPathReturnsTheValueOfANestedArrayByFollowingTheGivenPathIfPathIsString() {
		$path = 'Foo.Bar.Baz.2';
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$expectedResult = 'the value';
		$actualResult = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, $path);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function getValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, NULL);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueByPathReturnsNullIfTheSegementsOfThePathDontExist() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		$this->assertNULL(\TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Bax', 2)));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValueByPathReturnsNullIfThePathHasMoreSegmentsThanTheGivenArray() {
		$array = array('Foo' => array('Bar' => array('Baz' => 'the value')));
		$this->assertNULL(\TYPO3\FLOW3\Utility\Arrays::getValueByPath($array, array('Foo', 'Bar', 'Baz', 'Bux')));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function convertObjectToArrayConvertsNestedObjectsToArray() {
		$object = new \stdClass();
		$object->a = 'v';
		$object->b = new \stdClass();
		$object->b->c = 'w';
		$object->d = array('i' => 'foo', 'j' => 12, 'k' => TRUE, 'l' => new \stdClass());

		$array = \TYPO3\FLOW3\Utility\Arrays::convertObjectToArray($object);
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsArray() {
		$array = array();
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setValueByPathSetsValueRecursivelyIfPathIsString() {
		$array = array();
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')));
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setValueByPathRecursivelyMergesAnArray() {
		$array = array('foo' => array('bar' => 'should be overriden'), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array('baz' => 'The Value')), 'bar' => 'Baz');
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::setValueByPath($array, $path, 'The Value');
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\FLOW3\Utility\Arrays::setValueByPath($array, NULL, 'Some Value');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArray() {
		$subject = 'foobar';
		\TYPO3\FLOW3\Utility\Arrays::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setValueByPathThrowsExceptionIfSubjectIsNoArrayAccess() {
		$subject = new \stdClass();
		\TYPO3\FLOW3\Utility\Arrays::setValueByPath($subject, 'foo', 'bar');
	}

	/**
	 * @test
	 */
	public function setValueByLeavesInputArrayUnchanged() {
		$subject = $subjectBackup = array('foo' => 'bar');
		\TYPO3\FLOW3\Utility\Arrays::setValueByPath($subject, 'foo', 'baz');
		$this->assertEquals($subject, $subjectBackup);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unsetValueByPathDoesNotModifyAnArrayIfThePathWasNotFound() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'nonExistingKey');
		$expectedValue = $array;
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unsetValueByPathRemovesSpecifiedKey() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo', 'bar', 'baz');
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');;
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unsetValueByPathRemovesSpecifiedKeyIfPathIsString() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = 'foo.bar.baz';
		$expectedValue = array('foo' => array('bar' => array()), 'bar' => 'Baz');;
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function unsetValueByPathRemovesSpecifiedBranch() {
		$array = array('foo' => array('bar' => array('baz' => 'Some Value')), 'bar' => 'Baz');
		$path = array('foo');
		$expectedValue = array('bar' => 'Baz');;
		$actualValue = \TYPO3\FLOW3\Utility\Arrays::unsetValueByPath($array, $path);
		$this->assertEquals($expectedValue, $actualValue);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function unsetValueByPathThrowsExceptionIfPathIsNoArrayOrString() {
		$array = array('Foo' => array('Bar' => array('Baz' => array(2 => 'the value'))));
		\TYPO3\FLOW3\Utility\Arrays::unsetValueByPath($array, NULL);
	}
}
?>