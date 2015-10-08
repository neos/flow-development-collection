<?php
namespace TYPO3\Flow\Object\Proxy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A marker interface for Proxy Classes
 *
 */
interface ProxyInterface
{
    /**
     * Wake up method.
     *
     * Proxies need to have one as at least session handling relies on it.
     *
     * @return void
     */
    public function __wakeup();
}
