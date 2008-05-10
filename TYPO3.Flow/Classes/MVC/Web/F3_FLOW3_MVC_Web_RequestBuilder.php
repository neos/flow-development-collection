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
 * @version $Id:F3_FLOW3_MVC_Web_RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Builds a web request object from the raw HTTP information
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Web_RequestBuilder.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class F3_FLOW3_MVC_Web_RequestBuilder {

	/**
	 * @var F3_FLOW3_Component_ManagerInterface $componentManager: A reference to the Component Manager
	 */
	 protected $componentManager;

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $utilityEnvironment;

	/**
	 * @var F3_FLOW3_MVC_Web_RouterInterface
	 */
	protected $router;

	/**
	 * Constructs the Web Request Builder
	 *
	 * @param F3_FLOW3_Component_ManagerInterface $componentManager A reference to the component manager
	 * @param F3_FLOW3_Utility_Environment $utilityEnvironment A reference to the environment
	 * @param F3_FLOW3_MVC_Web_RouterInterface $router A router which routes the web request to a controller and action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Component_ManagerInterface $componentManager, F3_FLOW3_Utility_Environment $utilityEnvironment, F3_FLOW3_MVC_Web_RouterInterface $router) {
		$this->componentManager = $componentManager;
		$this->utilityEnvironment = $utilityEnvironment;
		$this->router = $router;
	}

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return F3_FLOW3_MVC_Web_Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$request = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectEnvironment($this->utilityEnvironment);
		$request->setRequestURI($this->utilityEnvironment->getRequestURI());
		$this->router->route($request);
		return $request;
	}
}
?>