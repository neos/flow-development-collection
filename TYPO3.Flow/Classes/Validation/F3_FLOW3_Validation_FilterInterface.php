<?php
declare(ENCODING = 'utf-8');

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
 * Contract for a filter 
 * 
 * @package		FLOW3
 * @subpackage	Validation
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @author		Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Validation_FilterInterface {

	/*
	 * Sets
	 */
	public function setProperty($propertyValue);
}

?>