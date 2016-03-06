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
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Core\Bootstrap;

/**
 * A general lock class.
 *
 * @api
 */
class Factory
{
    /**
     * @var array
     */
    protected static $runtimeCache;

    /**
     * @param string $subject
     * @param boolean $exclusiveLock TRUE to, acquire an exclusive (write) lock, FALSE for a shared (read) lock. An exclusive lock is the default.
     * @return Lock
     */
    public static function acquire($subject, $exclusiveLock = true)
    {
        return new Lock($subject, $exclusiveLock);
    }
}
