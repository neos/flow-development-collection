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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Signal Dispatcher Class
 */
final class DispatcherTest extends UnitTestCase
{
    #[Test]
    public function connectAllowsForConnectingASlotWithASignal(): void
    {

        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = $this->createMock(SlotFixture::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function connectAlsoAcceptsObjectsInPlaceOfTheClassName(): void
    {
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = $this->createMock(SlotFixture::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function connectAlsoAcceptsClosuresActingAsASlot(): void
    {
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = function () {
        };

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', false);

        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => false]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function wireAllowsForConnectingASlotWithASignal(): void
    {
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = $this->createMock(SlotFixture::class);

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', get_class($mockSlot), 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => get_class($mockSlot), 'method' => 'someSlotMethod', 'object' => null, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function wireAlsoAcceptsObjectsInPlaceOfTheClassName(): void
    {
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = $this->createMock(SlotFixture::class);

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', $mockSlot, 'someSlotMethod', false);

        $expectedSlots = [
            ['class' => null, 'method' => 'someSlotMethod', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function wireAlsoAcceptsClosuresActingAsASlot(): void
    {
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = function () {
        };

        $dispatcher = new Dispatcher();
        $dispatcher->wire(get_class($mockSignal), 'someSignal', $mockSlot, 'foo', false);

        $expectedSlots = [
            ['class' => null, 'method' => '__invoke', 'object' => $mockSlot, 'passSignalInformation' => false, 'useSignalInformationObject' => true]
        ];
        self::assertSame($expectedSlots, $dispatcher->getSlots(get_class($mockSignal), 'someSignal'));
    }

    #[Test]
    public function dispatchPassesTheSignalArgumentsToTheSlotMethod(): void
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            $arguments = func_get_args();
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('Foo', 'bar', $mockSlot, '', false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame(['bar', 'quux'], $arguments);
    }

    #[Test]
    public function dispatchPassesUnnamedSignalArgumentsToTheSlotMethod(): void
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            $arguments = func_get_args();
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('Foo', 'bar', $mockSlot, '', false);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('Foo', 'bar', ['bar', 'quux']);
        self::assertSame(['bar', 'quux'], $arguments);
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function dispatchRetrievesSlotInstanceFromTheObjectManagerIfOnlyAClassNameWasSpecified(): void
    {
        $slotClassName = 'Mock_' . md5(uniqid((string)mt_rand(), true));
        eval('#[\AllowDynamicProperties]'. chr(10) . 'class ' . $slotClassName . ' { function slot($foo, $baz) { $this->arguments = array($foo, $baz); } }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->willReturn(true);
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->willReturn($mockSlot);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'slot', false);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame($mockSlot->arguments, ['bar', 'quux']);
    }

    #[Test]
    public function dispatchThrowsAnExceptionIfTheSpecifiedClassOfASlotIsUnknown(): void
    {
        $this->expectException(InvalidSlotException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with('NonExistingClassName')->willReturn(false);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', 'NonExistingClassName', 'slot', false);
        $dispatcher->dispatch('Foo', 'bar', []);
    }

    #[Test]
    public function dispatchThrowsAnExceptionIfTheSpecifiedSlotMethodDoesNotExist(): void
    {
        $this->expectException(InvalidSlotException::class);
        $slotClassName = 'Mock_' . md5(uniqid((string)mt_rand(), true));
        eval('class ' . $slotClassName . ' {  }');
        $mockSlot = new $slotClassName();

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('isRegistered')->with($slotClassName)->willReturn(true);
        $mockObjectManager->expects($this->once())->method('get')->with($slotClassName)->willReturn($mockSlot);

        $dispatcher = new Dispatcher();
        $dispatcher->injectObjectManager($mockObjectManager);
        $dispatcher->connect('Foo', 'bar', $slotClassName, 'unknownMethodName', true);

        $dispatcher->dispatch('Foo', 'bar', ['foo' => 'bar', 'baz' => 'quux']);
    }

    #[Test]
    public function dispatchPassesArgumentContainingSlotInformationLastIfTheConnectionStatesSo(): void
    {
        $arguments = [];
        $mockSlot = function () use (&$arguments) {
            $arguments = func_get_args();
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('SignalClassName', 'methodName', $mockSlot, '', true);
        $dispatcher->injectObjectManager($mockObjectManager);

        $dispatcher->dispatch('SignalClassName', 'methodName', ['foo' => 'bar', 'baz' => 'quux']);
        self::assertSame(['bar', 'quux', 'SignalClassName::methodName'], $arguments);
    }

    #[Test]
    public function connectWithSignalNameStartingWithEmitShouldNotBeAllowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $mockSignal = $this->createMock(SignalFixture::class);
        $mockSlot = $this->createMock(SlotFixture::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect(get_class($mockSignal), 'emitSomeSignal', get_class($mockSlot), 'someSlotMethod', false);
    }

    #[Test]
    public function dispatchPassesSignalArgumentsAsReferenceInSignalInformation(): void
    {
        $mockSlot = function (SignalInformation $s) {
            $s->getSignalArguments()[0]['foo'] = 'bar';
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->wire('SignalClassName', 'methodName', $mockSlot);
        $dispatcher->injectObjectManager($mockObjectManager);

        $referencedArray = [];
        $passedArguments = [&$referencedArray];
        $dispatcher->dispatch('SignalClassName', 'methodName', $passedArguments);
        self::assertEquals('bar', $referencedArray['foo']);
    }

    #[Test]
    public function dispatchPassesSignalArgumentsAsReference(): void
    {
        $mockSlot = function (array &$array) {
            $array['foo'] = 'bar';
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->connect('SignalClassName', 'methodName', $mockSlot);
        $dispatcher->injectObjectManager($mockObjectManager);

        $referencedArray = [];
        $passedArguments = [&$referencedArray];
        $dispatcher->dispatch('SignalClassName', 'methodName', $passedArguments);
        self::assertEquals('bar', $referencedArray['foo']);
    }

    #[Test]
    public function dispatchPassesSignalInformationObjectIfWireWasUsed(): void
    {
        $receivedArguments = [];
        $mockSlot = function (SignalInformation $s) use (&$receivedArguments) {
            $receivedArguments = func_get_args();
        };

        $mockObjectManager = $this->createStub(ObjectManagerInterface::class);

        $dispatcher = new Dispatcher();
        $dispatcher->wire('SignalClassName', 'methodName', $mockSlot);
        $dispatcher->injectObjectManager($mockObjectManager);

        $passedArguments = ['bar', 'quux'];
        $dispatcher->dispatch('SignalClassName', 'methodName', $passedArguments);
        self::assertEquals([new SignalInformation('SignalClassName', 'methodName', $passedArguments)], $receivedArguments);
    }
}

/**
 * Fixtures providing a signal emitter and a slot method. The dispatcher only ever needs the
 * class name of a signal, and a slot has to be an existing method – hence these stand-ins.
 */
class SignalFixture
{
    public function emitSomeSignal(): void
    {
    }
}

class SlotFixture
{
    public function someSlotMethod(): void
    {
    }
}
