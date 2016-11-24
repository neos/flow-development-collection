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
 * A contract for a cache backend which provides Iterator functionality.
 *
 * @api
 */
interface IterableBackendInterface extends BackendInterface, \Iterator
{
}
