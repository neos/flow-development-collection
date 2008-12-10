<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Contract for a Request Processor. Objects of this kind are registered
 * via the Request Processor Chain Manager.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface RequestProcessorInterface {

	/**
	 * Processes the given request (ie. analyzes and modifies if necessary).
	 *
	 * @param \F3\FLOW3\MVC\Request $request The request
	 * @return void
	 */
	public function processRequest(\F3\FLOW3\MVC\Request $request);
}

?>