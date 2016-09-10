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
 * The "Unsatisfactory Formatter" exception
 *
 * Thrown when the I18n's FormatResolver was able to retrieve a formatter at all,
 * but did not satisfy (i.e. implement) the FormatterInterface.
 *
 * @api
 */
class InvalidFormatterException extends I18n\Exception
{
}
