<?php
namespace TYPO3\Flow\Tests\Functional\SignalSlot;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\SignalSlot\Dispatcher;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Test suite for Signal Slot
 *
 */
class SignalSlotTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function signalsDeclaredInAbstractClassesAreFunctionalInSubClasses()
    {
        $subClass = new Fixtures\SubClass();

        $dispatcher = $this->objectManager->get(Dispatcher::class);
        $dispatcher->connect(Fixtures\SubClass::class, 'something', $subClass, 'somethingSlot');

        $subClass->triggerSomethingSignalFromSubClass();
        $this->assertTrue($subClass->slotWasCalled, 'from sub class');

        $subClass->slotWasCalled = false;

        $subClass->triggerSomethingSignalFromAbstractClass();
        $this->assertTrue($subClass->slotWasCalled, 'from abstract class');
    }
}
