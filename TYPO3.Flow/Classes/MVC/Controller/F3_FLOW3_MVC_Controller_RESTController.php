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
 * @version $Id:F3::FLOW3::MVC::Controller::ActionController.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * An action controller for RESTful web services
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Controller::ActionController.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RESTController extends F3::FLOW3::MVC::Controller::ActionController {

	/**
	 * @var F3::FLOW3::MVC::Web::Request The current request
	 */
	protected $request;

	/**
	 * @var F3::FLOW3::MVC::Web::Response The response which will be returned by this action controller
	 */
	protected $response;

	/**
	 * Handles a web request. The result output is returned by altering the given response.
	 *
	 * Note that this REST controller only supports web requests!
	 *
	 * @param F3::FLOW3::MVC::Web::Request $request The request object
	 * @param F3::FLOW3::MVC::Web::Response $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(F3::FLOW3::MVC::Web::Request $request, F3::FLOW3::MVC::Web::Response $response) {
		parent::processRequest($request, $response);
	}

	/**
	 * Determines the name of the requested action and calls the action method accordingly.
	 * This implementation analyzes the HTTP request and chooses a matching REST-style action.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::MVC::Exception::NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 * @todo as soon as argument validation works as expected throw 400 on invalid identifier
	 */
	protected function callActionMethod() {
#		if ($this->arguments['identifier']->isValid() === FALSE) $this->throwStatus(400);

		if ($this->request->getControllerActionName() == 'default') {
			$actionName = 'default';
			switch ($this->request->getMethod()) {
				case F3::FLOW3::Utility::Environment::REQUEST_METHOD_GET :
					$actionName = ($this->arguments['identifier']->getValue() === NULL) ? 'list' : 'show';
				break;
				case F3::FLOW3::Utility::Environment::REQUEST_METHOD_POST :
					$actionName = 'create';
				break;
				case F3::FLOW3::Utility::Environment::REQUEST_METHOD_PUT :
					$actionName = 'update';
				break;
				case F3::FLOW3::Utility::Environment::REQUEST_METHOD_DELETE :
					$actionName = 'delete';
				break;
			}
			$this->request->setControllerActionName($actionName);
		}
		parent::callActionMethod();
	}

	/**
	 * Initializes (registers / defines) arguments of this controller.
	 *
	 * Override this method to add arguments which can later be accessed
	 * by the action methods.
	 *
	 * NOTE: If you override this method, don't forget to call the parent
	 * method as well.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeArguments() {
		$this->arguments->addNewArgument('identifier');
	}
}
?>