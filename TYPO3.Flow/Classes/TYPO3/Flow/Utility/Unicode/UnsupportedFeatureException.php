<?php
namespace TYPO3\Flow\Utility\Unicode;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
