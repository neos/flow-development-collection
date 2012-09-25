<?php
namespace TYPO3\Flow\I18n\TranslationProvider\Exception;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
class UnsupportedTranslationMethodException extends \TYPO3\Flow\I18n\Exception {

}

?>