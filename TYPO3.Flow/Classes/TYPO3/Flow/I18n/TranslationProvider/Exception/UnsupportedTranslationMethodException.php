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
 * An "Unsupported Translation Method" exception
 *
 * This exception can be thrown by a concrete class implementing
 * \TYPO3\Flow\I18n\TranslationProvider\TranslationProviderInterface when one
 * of the interface methods is not supported (eg. when storage method makes it
 * impossible).
 *
 * @api
 */
class UnsupportedTranslationMethodException extends I18n\Exception
{
}
