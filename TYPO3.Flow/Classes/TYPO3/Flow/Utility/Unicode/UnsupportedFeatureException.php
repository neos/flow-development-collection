<?php
namespace TYPO3\Flow\Utility\Unicode;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * Exception thrown if a feature is not supported by the PHP6 backport code.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UnsupportedFeatureException extends \Exception
{
}
