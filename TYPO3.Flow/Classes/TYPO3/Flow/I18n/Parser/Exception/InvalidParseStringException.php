<?php
namespace TYPO3\Flow\I18n\Parser\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Invalid Parse String" exception
 *
 * It is thrown when concrete parser encounters a character sequence which
 * cannot be parsed. This exception is only used internally.
 *
 * @api
 */
class InvalidParseStringException extends \TYPO3\Flow\I18n\Exception
{
}
