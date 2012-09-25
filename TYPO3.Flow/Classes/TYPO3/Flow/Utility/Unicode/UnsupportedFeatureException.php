<?php
namespace TYPO3\Flow\Utility\Unicode;

/*                                                                        *
 * This script belongs to the Flow package "Flow".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Exception thrown if a feature is not supported by the PHP6 backport code.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class UnsupportedFeatureException extends \Exception {
}

?>