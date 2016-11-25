<?php
namespace Neos\Flow\I18n\Cldr\Reader\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\I18n\Exception\InvalidArgumentException;

/**
 * The "Invalid Format Type" exception
 *
 * Thrown when $formatType parameter provided to any Readers' method is not
 * one of allowed values.
 *
 * @api
 */
class InvalidFormatTypeException extends InvalidArgumentException
{
}
