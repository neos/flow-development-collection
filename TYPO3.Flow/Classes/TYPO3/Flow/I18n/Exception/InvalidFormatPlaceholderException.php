<?php
namespace TYPO3\Flow\I18n\Exception;

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
 * The "Invalid Format Placeholder" exception
 *
 * Thrown when a placeholder in string (which looks like "{0,datetime}") is
 * invalid (ie. is not closed before next placeholder begins, or the end of the
 * string, etc).
 *
 * @api
 */
class InvalidFormatPlaceholderException extends \TYPO3\Flow\I18n\Exception {

}
