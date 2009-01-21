<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Monitor;

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
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 */

/**
 * Contract for a change detection strategy
 *
 * @package FLOW3
 * @subpackage Monitor
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface ChangeDetectionStrategyInterface {

	const STATUS_UNCHANGED = 0;
	const STATUS_CREATED = 1;
	const STATUS_CHANGED = 2;
	const STATUS_DELETED = 3;

	/**
	 * Checks if the specified file has changed
	 *
	 * @return integer One of the STATUS_* constants
	 */
	public function getFileStatus($pathAndFilename);

	/**
	 * Checks if the specified directory has changed
	 *
	 * @return integer One of the STATUS_* constants
	 */
	public function getDirectoryStatus($path);

}
?>