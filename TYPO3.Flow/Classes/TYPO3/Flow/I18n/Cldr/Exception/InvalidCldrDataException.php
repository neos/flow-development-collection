<?php
namespace TYPO3\Flow\I18n\Cldr\Exception;

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
 * The "Invalid CLDR Data" exception
 *
 * Thrown when file in CLDR repository is corrupted, or cannot be accessed.
 *
 * @api
 */
class InvalidCldrDataException extends I18n\Exception
{
}
