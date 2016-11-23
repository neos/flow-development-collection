<?php
namespace Neos\Cache\Backend;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
