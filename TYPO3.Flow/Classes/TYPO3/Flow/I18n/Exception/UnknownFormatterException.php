<?php
namespace TYPO3\FLOW3\I18n\Exception;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The "Unknown Formatter" exception
 *
 * Thrown when no suitable class can be found which would implement
 * \TYPO3\FLOW3\Formatter\FormatterInterface and have requested name suffixed with
 * "Formatter" at the same time.
 *
 * @api
 */
class UnknownFormatterException extends \TYPO3\FLOW3\I18n\Exception {

}

?>