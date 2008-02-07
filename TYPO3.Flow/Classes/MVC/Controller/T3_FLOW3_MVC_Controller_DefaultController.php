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
 * A Special Case of a Controller: If no controller could be resolved or no
 * controller has been specified in the request, this controller is chosen.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_Controller_DefaultController.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Controller_DefaultController extends T3_FLOW3_MVC_Controller_RequestHandlingController {

	/**
	 * Processes a generic request and returns a response
	 * 
	 * @param T3_FLOW3_MVC_Request			$request: The request
	 * @param T3_FLOW3_MVC_Response			$response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(T3_FLOW3_MVC_Request $request, T3_FLOW3_MVC_Response $response) {
		switch (get_class($request)) {
			case 'T3_FLOW3_MVC_Web_Request' :
				$this->processWebRequest($request, $response);
				break;
			default :
				$response->setContent(
					"\nWelcome to FLOW3!\n\n" .
					"This is the default view of the FLOW3 MVC component. You see this message because no \n" .
					"other view is available. Please refer to the Developer's Guide for more information \n" .
					"how to create and configure one.\n\n" .
					"Have fun! The FLOW3 Development Team\n" 
				);
		}
	}

	/**
	 * Processes a web request and returns a response
	 * 
	 * @param T3_FLOW3_MVC_Web_Request			$request: The request
	 * @param T3_FLOW3_MVC_Web_Response		$response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function processWebRequest(T3_FLOW3_MVC_Web_Request $request, T3_FLOW3_MVC_Web_Response $response) {
		$view = $this->componentManager->getComponent('T3_FLOW3_MVC_View_Default');
		$view->setRequest($request);
		$response->setContent($view->render());
	}

}

?>