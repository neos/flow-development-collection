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
 * An abstract base class for Controllers
 * 
 * @package		Framework
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_Controller_Abstract.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class T3_FLOW3_MVC_Controller_Abstract {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface A reference to the Component Manager
	 */
	protected $componentManager;

	/**
	 * @var T3_FLOW3_Package_ManagerInterface A reference to the Package Manager
	 */
	protected $packageManager;
	
	/**
	 * Constructs the controller.
	 *
	 * @param T3_FLOW3_Component_ManagerInterface		$componentManager: A reference to the Component Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(
			T3_FLOW3_Component_ManagerInterface $componentManager, 
			T3_FLOW3_Package_ManagerInterface $packageManager) {
		$this->componentManager = $componentManager;
		$this->packageManager = $packageManager;
	}

	/**
	 * Initializes this component after all dependencies have been resolved.
	 * 
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeComponent() {
		$this->initializeController();		
	}
	
	/**
	 * Initializes this controller.
	 * 
	 * Override this method for initializing your concrete controller implementation.
	 * Recommended actions for your controller initialization method are setting up the expected
	 * arguments and narrowing down the supported request types if neccessary.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeController() {
	}
}

?>