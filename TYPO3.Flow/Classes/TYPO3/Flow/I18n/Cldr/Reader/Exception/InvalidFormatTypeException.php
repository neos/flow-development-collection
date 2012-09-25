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
 * The "Invalid Format Type" exception
 *
 * Thrown when $formatType parameter provided to any Readers' method is not
 * one of allowed values.
 *
 * @api
 */
class InvalidFormatTypeException extends \TYPO3\Flow\I18n\Exception\InvalidArgumentException {

}

?>