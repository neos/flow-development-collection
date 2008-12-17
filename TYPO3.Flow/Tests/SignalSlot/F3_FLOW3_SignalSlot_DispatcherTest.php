<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\SignalSlot;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage SignalSlot
 * @version $Id$
 */


/**
 * Testcase for the Signal Dispatcher Class
 *
 * @package FLOW3
 * @subpackage SignalSlot
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DispatcherTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod');

		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL)
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
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'someSlotMethod');

		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot)
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
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', $mockSlot, 'foo');

		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot)
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

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('Foo', 'emitBar', $mockSlot);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
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

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockObjectManager->expects($this->once())->method('getObject')->with($slotClassName)->will($this->returnValue($mockSlot));

		$dispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'emitBar', $slotClassName, 'slot');

		$dispatcher->dispatch('Foo', 'emitBar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}
}
?>