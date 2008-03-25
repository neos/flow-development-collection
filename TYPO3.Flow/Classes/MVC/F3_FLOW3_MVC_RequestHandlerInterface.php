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
 * The interface for a request handler 
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:F3_FLOW3_MVC_RequestHandlerInterface.php 467 2008-02-06 19:34:56Z robert $
 * @author 		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_MVC_RequestHandlerInterface {
	
	/**
	 * Handles a raw request and sends the respsonse.
	 *
	 * @return void
	 */
	public function handleRequest();

	/**
	 * Checks if the request handler can handle the current request.
	 *
	 * @return boolean		TRUE if it can handle the request, otherwise FALSE
	 */
	public function canHandleRequest();
	
	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request. An integer > 0 means "I want to handle this request" where 
	 * "100" is default. "0" means "I am a fallback solution".
	 * 
	 * If the handler cannot handle the request, a LogicException should be
	 * thrown.
	 * 
	 * @return integer		The priority of the request handler
	 */
	public function getPriority();
}

?>