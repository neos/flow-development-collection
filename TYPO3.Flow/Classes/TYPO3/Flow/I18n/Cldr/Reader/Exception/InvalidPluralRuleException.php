<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Invalid Plural Rule" exception
 *
 * Thrown when plural rule equation from CLDR is invalid (which probably means
 * that CLDR repository is corrupted).
 *
 * @api
 */
class InvalidPluralRuleException extends \TYPO3\Flow\I18n\Cldr\Exception\InvalidCldrDataException
{
}
