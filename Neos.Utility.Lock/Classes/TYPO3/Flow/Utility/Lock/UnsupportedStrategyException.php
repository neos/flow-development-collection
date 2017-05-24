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

/**
 * A strategy unsupported exception
 *
 * This exception is thrown by locking strategies if the strategy is not supported on the current platform.
 *
 */
class UnsupportedStrategyException extends \TYPO3\Flow\Exception
{
}
