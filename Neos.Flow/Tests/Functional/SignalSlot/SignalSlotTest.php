<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\SignalSlot;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\Functional\SignalSlot\Fixtures\SubClass;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test suite for Signal Slot
 *
 */
final class SignalSlotTest extends FunctionalTestCase
{
    #[Test]
    public function signalsDeclaredInAbstractClassesAreFunctionalInSubClasses()
    {
        $subClass = new SubClass();

        $dispatcher = $this->objectManager->get(Dispatcher::class);
        $dispatcher->connect(SubClass::class, 'something', $subClass, 'somethingSlot');

        $subClass->triggerSomethingSignalFromSubClass();
        self::assertTrue($subClass->slotWasCalled, 'from sub class');

        $subClass->slotWasCalled = false;

        $subClass->triggerSomethingSignalFromAbstractClass();
        self::assertTrue($subClass->slotWasCalled, 'from abstract class');
    }

    #[Test]
    public function slotsReceiveArgumentsAsReference()
    {
        $subClass = new SubClass();

        $dispatcher = $this->objectManager->get(Dispatcher::class);
        $dispatcher->connect(SubClass::class, 'signalWithReferenceArgument', $subClass, 'referencedArraySlot');

        $subClass->triggerSignalWithByReferenceArgument();
        self::assertArrayHasKey('foo', $subClass->referencedArray);
        self::assertEquals('bar', $subClass->referencedArray['foo']);
    }

    #[Test]
    public function slotsReceiveArgumentsAsReferenceInSignalInformation()
    {
        $subClass = new SubClass();

        $dispatcher = $this->objectManager->get(Dispatcher::class);
        $dispatcher->wire(SubClass::class, 'signalWithReferenceArgument', $subClass, 'referencedArraySlotWithSignalInformation');

        $subClass->triggerSignalWithByReferenceArgument();
        self::assertArrayHasKey('foo', $subClass->referencedArray);
        self::assertEquals('bar', $subClass->referencedArray['foo']);
    }
}
