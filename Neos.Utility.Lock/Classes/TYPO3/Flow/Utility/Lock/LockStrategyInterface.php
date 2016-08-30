<?php
namespace TYPO3\Flow\Utility\Lock;

/*
 * This file is part of the Neos.Utility.Lock package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use malkusch\lock\util\DoubleCheckedLocking;
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
     * @param \Closure $callback
     * @return mixed Return value of the callback
     */
    public function synchronized($subject, \Closure $callback);

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return DoubleCheckedLocking
     */
    public function check($subject, \Closure $callback);
}
