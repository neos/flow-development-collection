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
 * The "Unknown Formatter" exception
 *
 * Thrown when no suitable class can be found which would implement
 * \TYPO3\Flow\Formatter\FormatterInterface and have requested name suffixed with
 * "Formatter" at the same time.
 *
 * @api
 */
class UnknownFormatterException extends I18n\Exception
{
}
