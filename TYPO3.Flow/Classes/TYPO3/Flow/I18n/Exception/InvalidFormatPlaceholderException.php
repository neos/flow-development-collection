<?php
namespace TYPO3\Flow\I18n\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
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
class InvalidFormatPlaceholderException extends \TYPO3\Flow\I18n\Exception
{
}
