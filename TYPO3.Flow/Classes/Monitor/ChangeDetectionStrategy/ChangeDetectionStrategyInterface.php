<?php
namespace TYPO3\FLOW3\Monitor\ChangeDetectionStrategy;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Contract for a change detection strategy
 *
 * @api
 */
interface ChangeDetectionStrategyInterface {

	const STATUS_UNCHANGED = 0;
	const STATUS_CREATED = 1;
	const STATUS_CHANGED = 2;
	const STATUS_DELETED = 3;

	/**
	 * Checks if the specified file has changed
	 *
	 * @param string $pathAndFilename
	 * @return integer One of the STATUS_* constants
	 * @api
	 */
	public function getFileStatus($pathAndFilename);

}
?>