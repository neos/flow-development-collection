<?php
namespace TYPO3\FLOW3\Tests\Unit\SignalSlot;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Testcase for the Signal Dispatcher Class
 *
 */
class DispatcherTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function connectAllowsForConnectingASlotWithASignal() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', FALSE);

		$expectedSlots = array(
			array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => NULL, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsObjectsInPlaceOfTheClassName() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', FALSE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function connectAlsoAcceptsClosuresActingAsASlot() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = function() { };

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', FALSE);

		$expectedSlots = array(
			array('class' => NULL, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => FALSE)
		);
		$this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesTheSignalArgumentsToTheSlotMethod() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('Foo', 'bar', $mockSlot, NULL, FALSE);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), $arguments);
	}

	/**
	 * @test
	 */
	public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethod() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with('TYPO3\FLOW3\Tests\Unit\SignalSlot\DispatcherTest')->will($this->returnValue('TYPO3\FLOW3\Tests\Unit\SignalSlot\DispatcherTest'));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', FALSE);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux'), self::$arguments);
	}

	/**
	 * @test
	 */
	public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethodIfNoObjectmanagerIsAvailable() {
		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', FALSE);

		$dispatcher->dispatch('Foo', 'bar', array('no' => 'object', 'manager' => 'exists'));
		$this->assertSame(array('object', 'exists'), self::$arguments);
	}

	/**
	 * A variable used in the above two tests.
	 * @var array
	 */
	static protected $arguments = array();

	/**
	 * A slot used in the above two tests.
	 *
	 * @return void
	 */
	static public function staticSlot() {
		self::$arguments = func_get_args();
	}

	/**
	 * @test
	 */
	public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified() {
		$slotClassName = 'Mock_' . md5(uniqid(mt_rand(), TRUE));
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'bar', $slotClassName, 'slot', FALSE);

		$dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\SignalSlot\Exception\InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(FALSE));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'bar', 'NonExistingClassName', 'slot', FALSE);
		$dispatcher->dispatch('Foo', 'bar', array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\SignalSlot\Exception\InvalidSlotException
	 */
	public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist() {
		$slotClassName = 'Mock_' . md5(uniqid(mt_rand(), TRUE));
		eval ('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
		$mockSlot = new $slotClassName();

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(TRUE));
		$mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->injectObjectManager($mockObjectManager);
		$dispatcher->connect('Foo', 'bar', $slotClassName, 'unknownMethodName', TRUE);

		$dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame($mockSlot->arguments, array('bar', 'quux'));
	}

	/**
	 * @test
	 */
	public function dispatchPassesArgumentContainingSlotInformationLastIfTheConnectionStatesSo() {
		$arguments = array();
		$mockSlot = function() use (&$arguments) { $arguments =  func_get_args(); };

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect('SignalClassName', 'methodName', $mockSlot, NULL, TRUE);
		$dispatcher->injectObjectManager($mockObjectManager);

		$dispatcher->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
		$this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function connectWithSignalNameStartingWithEmitShouldNotBeAllowed() {
		$mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
		$mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

		$dispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
		$dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', FALSE);
	}

}
?>