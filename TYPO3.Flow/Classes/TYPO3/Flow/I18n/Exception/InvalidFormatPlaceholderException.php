<?php
namespace TYPO3\Flow\I18n\Exception;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\I18n;

/**
 * The "Invalid Format Placeholder" exception
 *
 * Thrown when a placeholder in string (which looks like "{0,datetime}") is
 * invalid (ie. is not closed before next placeholder begins, or the end of the
 * string, etc).
 *
 * @api
 */
class InvalidFormatPlaceholderException extends I18n\Exception
{
}
