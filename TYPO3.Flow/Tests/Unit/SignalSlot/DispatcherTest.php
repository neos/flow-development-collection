<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\SignalSlot;

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
 * Testcase for the Signal Dispatcher Class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DispatcherTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', TRUE);

		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAlsoAcceptsObjectsInPlaceOfTheClassName() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod', TRUE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAlsoAcceptsClosuresActingAsASlot() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = function() { };

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo', TRUE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot, 'omitSignalInformation' => TRUE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'emitSomeSignal'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchPassesTheSignalArgumentsToTheSlotMethod() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('Foo', 'bar', $mockSlot, NULL, TRUE);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $arguments);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified() {
		$slotClassName = uniqid('Mock_');
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot', TRUE);

		$dispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\SignalSlot\Exception\InvalidSlotException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(FALSE));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'emitBar', 'NonExistingClassName', 'slot', TRUE);
		$dispatcher->dispatch('Foo', 'emitBar', array());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\SignalSlot\Exception\InvalidSlotException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist() {
		$slotClassName = uniqid('Mock_');
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'emitBar', $slotClassName, 'unknownMethodName', TRUE);

		$dispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatchPassesFirstArgumentContainingSlotInformationIfTheConnectionStatesSo() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('SignalClassName', 'methodName', $mockSlot, NULL, FALSE);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
	}

}
?>