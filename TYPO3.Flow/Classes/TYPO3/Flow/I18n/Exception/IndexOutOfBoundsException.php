<?php
namespace TYPO3\Flow\I18n\Exception;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\I18n;

/**
 * The "Index Out Of Bounds" exception
 *
 * Generic exception thrown when tried to access unexisting element (ie. with
 * too high index value).
 *
 * @api
 */
class IndexOutOfBoundsException extends I18n\Exception
{
}
