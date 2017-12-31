<?php
namespace Neos\Cache;

use Psr\Cache\CacheException as Psr6CacheException;
use Psr\SimpleCache\CacheException as Psr16CacheException;

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
 * A generic Cache exception
 *
 * @api
 */
class Exception extends \Exception implements Psr6CacheException, Psr16CacheException
{
}
