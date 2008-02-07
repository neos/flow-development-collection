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
 * An abstract View
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_View_Abstract.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @author Robert Lemke <robert@typo3.org>
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
abstract class T3_FLOW3_MVC_View_Abstract {

	/**
	 * @var T3_FLOW3_Component_ManagerInterface A reference to the Component Manager
	 */
	protected $componentManager;

	/**
	 * @var T3_FLOW3_Package_ManagerInterface A reference to the Package Manager
	 */
	protected $packageManager;
	
	/**
	 * Constructs the view.
	 *
	 * @param T3_FLOW3_Component_ManagerInterface		$componentManager: A reference to the Component Manager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	final public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
		$this->packageManager = $componentManager->getComponent('T3_FLOW3_Package_ManagerInterface');
		$this->initializeView();		
	}

	/**
	 * Initializes this view.
	 * 
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 */
	protected function initializeView() {
	}

	/**
	 * Renders the view
	 *
	 * @return string							The rendered view
	 */
	abstract public function render();
}

?>