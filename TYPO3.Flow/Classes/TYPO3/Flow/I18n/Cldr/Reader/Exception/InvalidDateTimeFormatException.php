<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Invalid DateTime Format" exception
 *
 * Thrown when date and / or time pattern does not conform constraints defined
 * in CLDR specification.
 *
 * @api
 */
class InvalidDateTimeFormatException extends \TYPO3\Flow\I18n\Exception\InvalidArgumentException
{
}
