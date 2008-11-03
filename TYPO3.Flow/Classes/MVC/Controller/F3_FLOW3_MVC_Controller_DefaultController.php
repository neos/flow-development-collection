<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Controller;

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
 * @version $Id:F3::FLOW3::MVC::Controller::DefaultController.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A Special Case of a Controller: If no controller could be resolved or no
 * controller has been specified in the request, this controller is chosen.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Controller::DefaultController.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DefaultController extends F3::FLOW3::MVC::Controller::RequestHandlingController {

	/**
	 * @var F3::FLOW3::MVC::View::DefaultView
	 */
	protected $defaultView;

	/**
	 * Injects the default view
	 *
	 * @param F3::FLOW3::MVC::View::DefaultView $defaultView The default view
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectDefaultView(F3::FLOW3::MVC::View::DefaultView $defaultView) {
		$this->defaultView = $defaultView;
	}

	/**
	 * Processes a generic request and returns a response
	 *
	 * @param F3::FLOW3::MVC::Request $request: The request
	 * @param F3::FLOW3::MVC::Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(F3::FLOW3::MVC::Request $request, F3::FLOW3::MVC::Response $response) {
		$request->setDispatched(TRUE);
		switch (get_class($request)) {
			case 'F3::FLOW3::MVC::Web::Request' :
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
	 * @param F3::FLOW3::MVC::Web::Request $request: The request
	 * @param F3::FLOW3::MVC::Web::Response $response: The response
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function processWebRequest(F3::FLOW3::MVC::Web::Request $request, F3::FLOW3::MVC::Web::Response $response) {
		$this->defaultView->setRequest($request);
		$response->setContent($this->defaultView->render());
	}

}

?>