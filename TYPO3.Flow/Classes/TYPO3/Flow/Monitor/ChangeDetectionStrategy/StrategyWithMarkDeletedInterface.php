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
 * Contract for a change detection strategy that allows the FileMonitor to mark a file deleted directly.
 *
 * @api
 */
interface StrategyWithMarkDeletedInterface {

	/**
	 * Notify the change strategy that this file was deleted and does not need to be tracked anymore.
	 *
	 * @param string $pathAndFilename
	 * @return void
	 */
	public function setFileDeleted($pathAndFilename);
}
