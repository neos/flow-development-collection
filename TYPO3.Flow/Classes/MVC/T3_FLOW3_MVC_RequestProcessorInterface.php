<?php
declare(encoding = 'utf-8');

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
 * Contract for a Request Processor. Objects of this kind are registered
 * via the Request Processor Chain Manager.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @author		Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_MVC_RequestProcessorInterface {

	/**
	 * Processes the given request (ie. analyzes and modifies if necessary).
	 *
	 * @param  T3_FLOW3_MVC_Request 	$request: The request
	 * @return void
	 */
	public function processRequest(T3_FLOW3_MVC_Request $request);
}

?>