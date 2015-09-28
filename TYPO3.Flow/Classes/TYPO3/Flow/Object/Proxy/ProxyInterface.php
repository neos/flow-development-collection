<?php
namespace TYPO3\Flow\Object\Proxy;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
