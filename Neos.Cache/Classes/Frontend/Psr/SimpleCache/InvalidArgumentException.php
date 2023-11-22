<?php
namespace Neos\Cache\Frontend\Psr\SimpleCache;

/*
 * This file is part of the Neos.Cache package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\SimpleCache\InvalidArgumentException as Psr16InvalidArgumentException;

/**
 * An invalid argument (usually an inacceptable cache key) was given to a PSR cache.
 */
class InvalidArgumentException extends \InvalidArgumentException implements Psr16InvalidArgumentException
{
}
