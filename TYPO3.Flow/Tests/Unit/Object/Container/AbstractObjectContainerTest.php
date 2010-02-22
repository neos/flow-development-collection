<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

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
 * Testcase for the abstract Object Container
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractObjectContainerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createCallsTheClassesCreateMethodPassesTheGivenParametersAndReturnsTheBuiltObject() {
		$objects = array(
			'Foo' => array(
				's' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_PROTOTYPE,
				'm' => '1234'
			)
		);

		$expectedObject = new \stdClass;

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('c1234'));
		$container->expects($this->once())->method('c1234')->with(array('parameter 1', 'parameter 2'))->will($this->returnValue($expectedObject));
		$container->_set('objects', $objects);

		$actualObject = $container->create('Foo', 'parameter 1', 'parameter 2');
		$this->assertSame($expectedObject, $actualObject);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\UnknownObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createThrowsAnExceptionIfTheSpecifiedObjectIsUnknown() {
		$container = $this->getMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->create('Foo');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\WrongScopeException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createThrowsAnExceptionIfTheSpecifiedObjectIsNotOfScopePrototype() {
		$objects = array(
			'Foo' => array('s' => \F3\FLOW3\Object\Container\DynamicObjectContainer::SCOPE_SINGLETON)
		);

		$container = $this->getAccessibleMock('F3\FLOW3\Object\Container\AbstractObjectContainer', array('dummy'));
		$container->_set('objects', $objects);
		$container->create('Foo');
	}


}
?>