<?php
namespace TYPO3\Flow\Monitor\ChangeDetectionStrategy;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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