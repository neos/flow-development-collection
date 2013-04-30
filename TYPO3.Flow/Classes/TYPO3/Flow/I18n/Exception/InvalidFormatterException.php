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
 * The "Unsatisfactory Formatter" exception
 *
 * Thrown when the I18n's FormatResolver was able to retrieve a formatter at all,
 * but did not satisfy (i.e. implement) the FormatterInterface.
 *
 * @api
 */
class InvalidFormatterException extends \TYPO3\Flow\I18n\Exception {

}

?>