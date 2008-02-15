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
 * A multi action controller
 *
 * @package    Framework
 * @subpackage MVC
 * @version    $Id:T3_FLOW3_MVC_Controller_ActionController.php 467 2008-02-06 19:34:56Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Controller_ActionController extends T3_FLOW3_MVC_Controller_RequestHandlingController {

	/**
	 * @var (not used yet)
	 */
	protected $configuration;

	/**
	 * @var string Method name of the default action. Set it to the name of another action to define an alternative method as the default action.
	 */
	protected $defaultActionMethodName = 'defaultAction';

	/**
	 * @var boolean If initalizeView() should be called on an action invocation.
	 */
	protected $initalizeView = TRUE;

	/**
	 * @var T3_FLOW3_MVC_View_AbstractView By default a view with the same name as the current action is provided. Contains NULL if none was found.
	 */
	protected $view = NULL;

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param  T3_FLOW3_MVC_Request $request: The request object
	 * @param  T3_FLOW3_MVC_Response $response The response, modified by this handler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequest(T3_FLOW3_MVC_Request $request, T3_FLOW3_MVC_Response $response) {
		parent::processRequest($request, $response);
		$this->callActionMethod();
	}

	/**
	 * Determines the name of the requested action and calls the action method accordingly.
	 * If no action was specified, the "default" action is assumed.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws T3_FLOW3_MVC_Exception_NoSuchAction if the action specified in the request object does not exist (and if there's no default action either).
	 */
	protected function callActionMethod() {
		$actionMethodName = ($this->request->getActionName() == 'default') ? $this->defaultActionMethodName : $this->request->getActionName() . 'Action';

		if (!method_exists($this, $actionMethodName)) throw new T3_FLOW3_MVC_Exception_NoSuchAction('An action "' . $this->request->getActionName() . '" does not exist in controller "' . get_class($this) . '".', 1186669086);
		$this->initializeAction();
		if ($this->initalizeView) $this->initializeView();
		call_user_func_array(array($this, $actionMethodName), array());
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
		$explodedControllerName = explode('_', $this->request->getControllerName());
		if (is_array($explodedControllerName)) {
			$possibleViewName = 'T3_' . $explodedControllerName[1] . '_View_' . $explodedControllerName[3] . '_' . T3_PHP6_Functions::ucfirst($this->request->getActionName());
			if ($this->componentManager->isComponentRegistered($possibleViewName)) {
				$this->view = $this->componentManager->getComponent($possibleViewName);
				return;
			}
		}
		$this->view = $this->componentManager->getComponent('T3_FLOW3_MVC_View_Empty');
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
		 $output = 'No default action has been implemented yet for this controller.';
		 $this->response->appendContent($output);
	}
}
?>