<?php
namespace TYPO3\Flow\I18n\Cldr\Reader\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * The "Unable To Find Format" exception
 *
 * Thrown when string format was not returned from CLDR repository (which
 * probably is corrupted).
 *
 * @api
 */
class UnableToFindFormatException extends \TYPO3\Flow\I18n\Cldr\Exception\InvalidCldrDataException
{
}
