<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 */
class ClassWithInitializeObjectMethod
{
    public $reason;

    /**
     * Call the object lifecycle method
     *
     * @param mixed $reason why initializeObject is called.
     */
    public function initializeObject($reason)
    {
        $this->reason = $reason;
    }
}
