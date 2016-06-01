<?php
namespace TYPO3\Flow\Tests\Unit\SignalSlot;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


/**
 * Testcase for the Signal Dispatcher Class
 *
 */
class DispatcherTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function connectAllowsForConnectingASlotWithASignal()
    {
        $mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
        $mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', false);

        $expectedSlots = array(
            array('class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => false)
        );
        $this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsObjectsInPlaceOfTheClassName()
    {
        $mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
        $mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', false);

        $expectedSlots = array(
            array('class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => false)
        );
        $this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsClosuresActingAsASlot()
    {
        $mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
        $mockSlot = function () {
        };

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', false);

        $expectedSlots = array(
            array('class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => false)
        );
        $this->assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheSlotMethod()
    {
        $arguments = array();
        $mockSlot = function () use (&$arguments) {
            $arguments =  func_get_args();
        };

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect('Foo', 'bar', $mockSlot, null, false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
        $this->assertSame(array('bar', 'quux'), $arguments);
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethod()
    {
        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->any())->method('getClassNameByObjectName')->with('TYPO3\Flow\Tests\Unit\SignalSlot\DispatcherTest')->will($this->returnValue('TYPO3\Flow\Tests\Unit\SignalSlot\DispatcherTest'));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
        $this->assertSame(array('bar', 'quux'), self::$arguments);
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethodIfNoObjectmanagerIsAvailable()
    {
        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', false);

        $dispatcher->dispatch('Foo', 'bar', array('no' => 'object', 'manager' => 'exists'));
        $this->assertSame(array('object', 'exists'), self::$arguments);
    }

    /**
     * A variable used in the above two tests.
     * @var array
     */
    protected static $arguments = array();

    /**
     * A slot used in the above two tests.
     *
     * @return void
     */
    public static function staticSlot()
    {
        self::$arguments = func_get_args();
    }

    /**
     * @test
     */
    public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified()
    {
        $slotClassName = 'Mock_' . md5(uniqid(mt_rand(), true));
        eval('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(true));
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'slot', false);

        $dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
        $this->assertSame($mockSlot->arguments, array('bar', 'quux'));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown()
    {
        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->will($this->returnValue(false));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', 'NonExistingClassName', 'slot', false);
        $dispatcher->dispatch('Foo', 'bar', array());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\SignalSlot\Exception\InvalidSlotException
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist()
    {
        $slotClassName = 'Mock_' . md5(uniqid(mt_rand(), true));
        eval('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->will($this->returnValue(true));
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->will($this->returnValue($mockSlot));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'unknownMethodName', true);

        $dispatcher->dispatch('Foo', 'bar', array('foo' => 'bar', 'baz' => 'quux'));
        $this->assertSame($mockSlot->arguments, array('bar', 'quux'));
    }

    /**
     * @test
     */
    public function dispatchPassesArgumentContainingSlotInformationLastIfTheConnectionStatesSo()
    {
        $arguments = array();
        $mockSlot = function () use (&$arguments) {
            $arguments =  func_get_args();
        };

        $mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect('SignalClassName', 'methodName', $mockSlot, null, true);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('SignalClassName', 'methodName', array('foo' => 'bar', 'baz' => 'quux'));
        $this->assertSame(array('bar', 'quux', 'SignalClassName::methodName'), $arguments);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function connectWithSignalNameStartingWithEmitShouldNotBeAllowed()
    {
        $mockSignal = $this->getMock('ClassA', array('emitSomeSignal'));
        $mockSlot = $this->getMock('ClassB', array('someSlotMethod'));

        $dispatcher = new \TYPO3\Flow\SignalSlot\Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', false);
    }
}
