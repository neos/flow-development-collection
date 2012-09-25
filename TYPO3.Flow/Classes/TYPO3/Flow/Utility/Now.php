<?php
namespace TYPO3\Flow\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A unique DateTime object which serves as a reliable reference for the time "now"
 * for all parts of Flow and its packages. It also improves testability of code
 * relying on a certain time.
 *
 * At any place you'd normally call PHP's time() function or create a DateTime
 * object with the current time, you can instead use this instance.
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Now extends \DateTime {
}

?>
