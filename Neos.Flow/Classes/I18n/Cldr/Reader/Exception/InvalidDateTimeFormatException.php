<?php
namespace Neos\Flow\I18n\Cldr\Reader\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\Exception\InvalidArgumentException;

/**
 * The "Invalid DateTime Format" exception
 *
 * Thrown when date and / or time pattern does not conform constraints defined
 * in CLDR specification.
 *
 * @api
 */
class InvalidDateTimeFormatException extends InvalidArgumentException
{
}
