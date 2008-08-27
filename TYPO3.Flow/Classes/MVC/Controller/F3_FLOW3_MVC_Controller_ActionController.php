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
 * @version $Id:F3_FLOW3_MVC_Controller_ActionController.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A multi action controller
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Controller_ActionController.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_ActionController extends F3_FLOW3_MVC_Controller_RequestHandlingController {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface
	 */
	protected $componentManager;

	/**
	 * @var string Method name of the default action. Set it to the name of another action to define an alternative method as the default action.
	 */
	protected $defaultActionMethodName = 'defaultAction';

	/**
	 * @var boolean If initializeView() should be called on an action invocation.
	 */
	protected $initializeView = TRUE;

	/**
	 * @var F3_FLOW3_MVC_View_AbstractView By default a view with the same name as the current action is provided. Contains NULL if none was found.
	 */
	protected $view = NULL;

	/**
	 * Injects the component manager
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentManager(F3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param F3_FLOW3_MVC_Request $request The request object
	 * @param F3_FLOW3_MVC_Response $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(F3_FLOW3_MVC_Request $request, F3_FLOW3_MVC_Response $response) {
		parent::processRequest($request, $response);
		$this->callActionMethod();
	}

	/**
	 * Determines the name of the requested action and calls the action method accordingly.
	 * If no action was specified, the "default" action is assumed.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_MVC_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function callActionMethod() {
		$actionMethodName = ($this->request->getControllerActionName() == 'default') ? $this->defaultActionMethodName : $this->request->getControllerActionName() . 'Action';

		if (!method_exists($this, $actionMethodName)) throw new F3_FLOW3_MVC_Exception_NoSuchAction('An action "' . $this->request->getControllerActionName() . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		$this->initializeAction();
		if ($this->initializeView) $this->initializeView();
		$actionResult = call_user_func_array(array($this, $actionMethodName), array());
		if (is_string($actionResult) && F3_PHP6_Functions::strlen($actionResult) > 0) {
			$this->response->appendContent($actionResult);
		}
	}

	/**
	 * Prepares a view for the current action and stores it in $this->view.
	 * By default, this method tries to locate a view with a name matching
	 * the current action.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeView() {
		$possibleViewName = str_replace('Controller', 'View', $this->request->getControllerComponentNamePattern());
		$possibleViewName = str_replace('@package', $this->request->getControllerPackageKey(), $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->request->getControllerName(), $possibleViewName);
		$possibleViewName .= '_'  . F3_PHP6_Functions::ucfirst($this->request->getControllerActionName());

		$viewComponentName = $this->componentManager->getCaseSensitiveComponentName($possibleViewName . F3_PHP6_Functions::ucfirst($this->request->getFormat()));
		if ($viewComponentName === FALSE) {
			$viewComponentName = $this->componentManager->getCaseSensitiveComponentName($possibleViewName);
		}
		if ($viewComponentName === FALSE) {
			$viewComponentName = 'F3_FLOW3_MVC_View_Empty';
		}

		$this->view = $this->componentFactory->getComponent($viewComponentName);
		$this->view->setRequest($this->request);
	}

	/**
	 * Initializes the controller before invoking an action method.
	 *
	 * Override this method to solve tasks which all actions have in
	 * common.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeAction() {
	}

	/**
	 * The default action of this controller.
	 *
	 * This method should always be overridden by the concrete action
	 * controller implementation.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function defaultAction() {
		return 'No default action has been implemented yet for this controller.';
	}
}
?>