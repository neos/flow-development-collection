<?php
namespace TYPO3\Flow\Utility\Lock;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
