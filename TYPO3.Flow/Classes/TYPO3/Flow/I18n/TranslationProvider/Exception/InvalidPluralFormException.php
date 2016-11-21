<?php
namespace TYPO3\Flow\I18n\TranslationProvider\Exception;

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
 * An "Invalid Plural Form" exception
 *
 * This exception is thrown when one requests translation from the translation
 * provider, passing as parameter plural form which is not used in language
 * defined in provided locale.
 *
 * @api
 */
class InvalidPluralFormException extends I18n\Exception
{
}
