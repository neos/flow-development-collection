<?php
namespace Neos\Flow\Tests\Functional\SignalSlot\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\SignalSlot\SignalInformation;

/**
 * A concrete class for testing signals in abstract classes
 *
 */
class SubClass extends AbstractClass
{
    public $slotWasCalled = false;

    public $referencedArray = [];

    /**
     * @return void
     */
    public function triggerSomethingSignalFromSubClass()
    {
        $this->emitSomething();
    }

    public function triggerSignalWithByReferenceArgument()
    {
        $this->referencedArray = [];
        $this->emitSignalWithReferenceArgument($this->referencedArray);
    }

    /**
     * @Flow\Signal
     * @return void
     */
    public function emitSomething()
    {
    }

    /**
     * @Flow\Signal
     */
    public function emitSignalWithReferenceArgument(array &$array): void
    {
    }

    /**
     * @return void
     */
    public function somethingSlot()
    {
        $this->slotWasCalled = true;
    }

    public function referencedArraySlot(array &$array): void
    {
        $array['foo'] = 'bar';
    }

    public function referencedArraySlotWithSignalInformation(SignalInformation $si): void
    {
        $si->getSignalArguments()['array']['foo'] = 'bar';
    }
}
