<?php
namespace TYPO3\Flow\Tests\Functional\SignalSlot\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

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
