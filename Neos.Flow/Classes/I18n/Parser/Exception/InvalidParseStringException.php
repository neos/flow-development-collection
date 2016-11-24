<?php
namespace Neos\Flow\I18n\Parser\Exception;

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
 * The "Invalid Parse String" exception
 *
 * It is thrown when concrete parser encounters a character sequence which
 * cannot be parsed. This exception is only used internally.
 *
 * @api
 */
class InvalidParseStringException extends I18n\Exception
{
}
