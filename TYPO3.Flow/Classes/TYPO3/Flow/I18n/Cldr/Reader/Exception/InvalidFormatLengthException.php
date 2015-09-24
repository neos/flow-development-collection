<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Invalid Format Length" exception
 *
 * Thrown when $formatLength parameter provided to any Readers' method is not
 * one of allowed values.
 *
 * @api
 */
class InvalidFormatLengthException extends \TYPO3\Flow\I18n\Exception\InvalidArgumentException
{
}
