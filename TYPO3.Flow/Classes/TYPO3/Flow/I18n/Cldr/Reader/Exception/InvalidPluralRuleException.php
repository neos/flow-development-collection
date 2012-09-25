<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

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
 * The "Invalid Plural Rule" exception
 *
 * Thrown when plural rule equation from CLDR is invalid (which probably means
 * that CLDR repository is corrupted).
 *
 * @api
 */
class InvalidPluralRuleException extends \TYPO3\Flow\I18n\Cldr\Exception\InvalidCldrDataException {

}

?>