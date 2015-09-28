<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A contract for a cache backend which can be frozen.
 *
 * @api
 */
interface FreezableBackendInterface extends BackendInterface
{
    /**
     * Freezes this cache backend.
     *
     * All data in a frozen backend remains unchanged and methods which try to add
     * or modify data result in an exception thrown. Possible expiry times of
     * individual cache entries are ignored.
     *
     * On the positive side, a frozen cache backend is much faster on read access.
     * A frozen backend can only be thawn by calling the flush() method.
     *
     * @return void
     */
    public function freeze();

    /**
     * Tells if this backend is frozen.
     *
     * @return boolean
     */
    public function isFrozen();
}
