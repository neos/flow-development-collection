<?php
namespace Neos\Flow\I18n\Cldr\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\I18n;

/**
 * The "Invalid CLDR Data" exception
 *
 * Thrown when file in CLDR repository is corrupted, or cannot be accessed.
 *
 * @api
 */
class InvalidCldrDataException extends I18n\Exception
{
}
