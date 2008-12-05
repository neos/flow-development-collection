<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence::Aspect;

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
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * An interface used to introduce certain methods to support object persistence
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface DirtyMonitoringInterface {

	public function isNew();
	public function isDirty($propertyName);
		// the $joinPoint argument here is a special case, as the introduced
		// method is used from within an advice and "externally", thus we need
		// to handle this specially
	public function memorizeCleanState(F3::FLOW3::AOP::JoinPointInterface $joinPoint = NULL);

}
?>