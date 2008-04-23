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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_DefaultRequestHandler.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A Special Case of a Request Handler: This default handler is used, if no other request
 * handler was found which could handle the request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_DefaultRequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_DefaultRequestHandler implements F3_FLOW3_MVC_RequestHandlerInterface {

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		echo ('FLOW3: This is the default request handler - no other suitable request handler could be determined.');
	}

	/**
	 * This request handler can handle any request, as it is the default request handler.
	 *
	 * @return boolean TRUE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function canHandleRequest() {
		return TRUE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler. Always "0" = fallback.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 0;
	}
}

?>