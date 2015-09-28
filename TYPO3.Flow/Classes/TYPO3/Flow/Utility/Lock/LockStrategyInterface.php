<?php
namespace TYPO3\Flow\Utility\Lock;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Contract for a lock strategy.
 *
 * @api
 */
interface LockStrategyInterface
{
    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock.
     * @return void
     */
    public function acquire($subject, $exclusiveLock);

    /**
     * @return boolean TRUE on success, FALSE otherwise
     */
    public function release();
}
