<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\SignalSlot;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\SignalSlot\Exception\InvalidSlotException;
use Neos\Flow\SignalSlot\SignalInformation;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Signal Dispatcher Class
 */
class DispatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function connectAllowsForConnectingASlotWithASignal(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = $this->getMockBuilder('stdClass')->setMethods(['someSlotMethod'])->getMock();

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsObjectsInPlaceOfTheClassName(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = $this->getMockBuilder('stdClass')->setMethods(['someSlotMethod'])->getMock();

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function connectAlsoAcceptsClosuresActingAsASlot(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = function () {
        };

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', false);

        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function wireAllowsForConnectingASlotWithASignal(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = $this->getMockBuilder('stdClass')->setMethods(['someSlotMethod'])->getMock();

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function wireAlsoAcceptsObjectsInPlaceOfTheClassName(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = $this->getMockBuilder('stdClass')->setMethods(['someSlotMethod'])->getMock();

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function wireAlsoAcceptsClosuresActingAsASlot(): void
    {
        $mockSignal = $this->getMockBuilder('stdClass')->setMethods(['emitSomeSignal'])->getMock();
        $mockSlot = function () {
        };

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', false);

        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheSlotMethod(): void
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            $arguments = func_get_args();
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('Foo', 'bar', $mockSlot, '', false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame(['bar', 'quux'], $arguments);
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethod(): void
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getClassNameByObjectName')->with(DispatcherTest::class)->willReturn(DispatcherTest::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame(['bar', 'quux'], self::$arguments);
    }

    /**
     * @test
     */
    public function dispatchPassesTheSignalArgumentsToTheStaticSlotMethodIfNoObjectmanagerIsAvailable(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->connect('Foo', 'bar', get_class($this), '::staticSlot', false);

        $dispatcher->dispatch('Foo', 'bar', ['no' => 'object', 'manager' => 'exists']);
        self::assertSame(['object', 'exists'], self::$arguments);
    }

    /**
     * A variable used in the above two tests.
     *
     * @var array
     */
    protected static $arguments = [];

    /**
     * A slot used in the above two tests.
     *
     * @return void
     */
    public static function staticSlot(): void
    {
        self::$arguments = func_get_args();
    }

    /**
     * @test
     */
    public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified(): void
    {
        $slotClassName = 'Mock_' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('isRegistered')->with($slotClassName)->willReturn(true);
        $mockObjectManager->expects(self::once())->method('get')->with($slotClassName)->willReturn($mockSlot);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'slot', false);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown(): void
    {
        $this->expectException(InvalidSlotException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('isRegistered')->with('NonExistingClassName')->willReturn(false);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', 'NonExistingClassName', 'slot', false);
        $dispatcher->dispatch('Foo', 'bar', []);
    }

    /**
     * @test
     */
    public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist(): void
    {
        $this->expectException(InvalidSlotException::class);
        $slotClassName = 'Mock_' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $slotClassName . ' {  }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('isRegistered')->with($slotClassName)->willReturn(true);
        $mockObjectManager->expects(self::once())->method('get')->with($slotClassName)->willReturn($mockSlot);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'unknownMethodName', true);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
    }

    /**
     * @test
     */
    public function dispatchPassesArgumentContainingSlotInformationLastIfTheConnectionStatesSo(): void
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            $arguments = func_get_args();
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('SignalClassName', 'methodName', $mockSlot, '', true);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('SignalClassName', 'methodName', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame(['bar', 'quux', 'SignalClassName::methodName'], $arguments);
    }

    /**
     * @test
     */
    public function connectWithSignalNameStartingWithEmitShouldNotBeAllowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockSignal = $this->getMockBuilder('stdClass')->addMethods(['emitSomeSignal'])->getMock();
        $mockSlot = $this->getMockBuilder('stdClass')->addMethods(['someSlotMethod'])->getMock();

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', false);
    }

    /**
     * @test
     */
    public function dispatchPassesSignalInformationObjectIfWireWasUsed(): void
    {
        $receivedArguments = [];
        $mockSlot = function (SignalInformation $s) use (&$receivedArguments) {
            $receivedArguments = func_get_args();
        };

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->wire('SignalClassName', 'methodName', $mockSlot);
        $dispatcher->injectObjectManager($mockObjectManager);

        $passedArguments = ['bar', 'quux'];
        $dispatcher->dispatch('SignalClassName', 'methodName', $passedArguments);
        self::assertEquals([new SignalInformation('SignalClassName', 'methodName', $passedArguments)], $receivedArguments);
    }
}
