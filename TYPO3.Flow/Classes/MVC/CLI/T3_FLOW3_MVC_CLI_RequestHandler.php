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
 * The generic command line interface request handler for the MVC framework.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_CLI_RequestHandler.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_CLI_RequestHandler implements T3_FLOW3_MVC_RequestHandlerInterface {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface Reference to the component manager
	 */
	protected $componentManager;
	
	/**
	 * @var T3_FLOW3_Utility_Environment Reference to the environment utility component
	 */
	protected $utilityEnvironment;
	
	/**
	 * Constructs the CLI Request Handler
	 *
	 * @param  T3_FLOW3_Component_ManagerInterface		$componentManager: A reference to the component manager
	 * @param  T3_FLOW3_Utility_Environment 			$utilityEnvironment: Reference to the environment utility component
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager, T3_FLOW3_Utility_Environment $utilityEnvironment) {
		$this->componentManager = $componentManager;
		$this->utilityEnvironment = $utilityEnvironment;
	}
		
	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_CLI_RequestBuilder')->build();
		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_CLI_Response');
		$request->lock();

		$dispatcher = $this->componentManager->getComponent('T3_FLOW3_MVC_Dispatcher');
		$dispatcher->dispatch($request, $response);

		$response->send();
	}
	
	/**
	 * This request handler can handle any command line request.
	 *
	 * @return boolean			If the request is a command line request, TRUE otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canHandleRequest() {
		return ($this->utilityEnvironment->getSAPIName() == 'cli');
	}
	
	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request. 
	 * 
	 * @return integer		The priority of the request handler.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 100;
	}
	
}
?>