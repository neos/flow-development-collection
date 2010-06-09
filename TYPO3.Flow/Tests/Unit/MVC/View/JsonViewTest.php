<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * Testcase for the JSON view
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class JsonViewTest extends \F3\Testing\BaseTestCase {
	
	public function jsonViewTestData() {
		$output = array();
		
		$object = new \stdClass();
		$object->value1 = 'foo';
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value1' => 'foo', 'value2' => 1);
		$output[] = array($object, $configuration, $expected, 'all direct child properties should be serialized');
		
		$configuration = array('only' => array('value1'));
		$expected = array('value1' => 'foo');
		$output[] = array($object, $configuration, $expected, 'if "only" properties are specified, only these should be serialized');
		
		$configuration = array('exclude' => array('value1'));
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'if "exclude" properties are specified, they should not be serialized');
		
		$object = new \stdClass();
		$object->value1 = new \stdClass();
		$object->value1->subvalue1 = 'Foo';
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'by default, sub objects of objects should not be serialized.');

		$object = new \stdClass();
		$object->value1 = array('subarray' => 'value');
		$object->value2 = 1;
		$configuration = array();
		$expected = array('value2' => 1);
		$output[] = array($object, $configuration, $expected, 'by default, sub arrays of objects should not be serialized.');

		$object = array('foo' => 'bar', 1 => 'baz', 'deep' => array('test' => 'value'));
		$configuration = array();
		$expected = array('foo' => 'bar', 1 => 'baz', 'deep' => array('test' => 'value'));
		$output[] = array($object, $configuration, $expected, 'associative arrays should be serialized deeply');

		$object = array('foo', 'bar');
		$configuration = array();
		$expected = array('foo', 'bar');
		$output[] = array($object, $configuration, $expected, 'numeric arrays should be serialized');

		$nestedObject = new \stdClass();
		$nestedObject->value1 = 'foo';
		$object = array($nestedObject);
		$configuration = array();
		$expected = array(array('value1' => 'foo'));
		$output[] = array($object, $configuration, $expected, 'array of objects should be serialized');

		return $output;
	}
	
	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @dataProvider jsonViewTestData
	 */
	public function testTransformValue($object, $configuration, $expected, $description) {
		$jsonView = $this->getAccessibleMock('F3\FLOW3\MVC\View\JsonView', array('dummy'), array(), '', FALSE);

		$actual = $jsonView->_call('transformValue', $object, $configuration);

		$this->assertEquals($expected, $actual, $description);
	}

}