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
 * An abstract class with a signal
 *
 */
abstract class AbstractClass
{
    /**
     * @return void
     */
    public function triggerSomethingSignalFromAbstractClass()
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
}
