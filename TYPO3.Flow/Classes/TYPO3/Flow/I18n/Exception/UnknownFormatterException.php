<?php
namespace TYPO3\Flow\I18n\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Unknown Formatter" exception
 *
 * Thrown when no suitable class can be found which would implement
 * \TYPO3\Flow\Formatter\FormatterInterface and have requested name suffixed with
 * "Formatter" at the same time.
 *
 * @api
 */
class UnknownFormatterException extends \TYPO3\Flow\I18n\Exception
{
}
