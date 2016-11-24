<?php
namespace Neos\Flow\I18n\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n;

/**
 * The "Invalid Argument" exception
 *
 * Generic exception thrown when any (most probably string) argument of some
 * method does not conforms constraints.
 *
 * @api
 */
class InvalidArgumentException extends I18n\Exception
{
}
