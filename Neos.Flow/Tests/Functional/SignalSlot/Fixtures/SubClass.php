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

/**
 * A concrete class for testing signals in abstract classes
 *
 */
class SubClass extends AbstractClass
{
    public $slotWasCalled = false;

    /**
     * @return void
     */
    public function triggerSomethingSignalFromSubClass()
    {
        $this->emitSomething();
    }

    /**
     * @Flow\Signal
     * @return void
     */
    public function emitSomething()
    {
    }

    /**
     * @return void
     */
    public function somethingSlot()
    {
        $this->slotWasCalled = true;
    }
}
