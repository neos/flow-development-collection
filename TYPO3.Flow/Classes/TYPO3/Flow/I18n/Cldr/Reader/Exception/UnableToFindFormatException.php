<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\I18n\Cldr\Exception\InvalidCldrDataException;

/**
 * The "Unable To Find Format" exception
 *
 * Thrown when string format was not returned from CLDR repository (which
 * probably is corrupted).
 *
 * @api
 */
class UnableToFindFormatException extends InvalidCldrDataException
{
}
