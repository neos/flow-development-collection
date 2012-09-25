<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

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

use TYPO3\Flow\Monitor\FileMonitor;

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

	/**
	 * Creates a link to the file monitor using the strategy
	 *
	 * @param \TYPO3\Flow\Monitor\FileMonitor $fileMonitor
	 * @return mixed
	 */
	public function setFileMonitor(FileMonitor $fileMonitor);

	/**
	 * Commit any necessary data, like the current modification time.
	 *
	 * @return void
	 */
	public function shutdownObject();

}
?>