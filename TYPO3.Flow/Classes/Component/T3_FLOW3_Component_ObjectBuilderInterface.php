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
 * Component Object Builder interface
 * 
 * @package		FLOW3
 * @subpackage	Component
 * @version 	$Id:T3_FLOW3_Component_ObjectBuilderInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_Component_ObjectBuilderInterface {

	/**
	 * Constructor
	 *
	 * @param  T3_FLOW3_Component_Manager $componentManager: A reference to the component manager - used for fetching other component objects while solving dependencies
	 * @return void
	 */
	public function __construct(T3_FLOW3_Component_ManagerInterface $componentManager);	
	
	/**
	 * Creates and returns a ready to use component object of the specified type.
	 * During the building process all depencencies are resolved and injected.
	 *
	 * @param  string $componentName: Name of the component to create a component object for
	 * @return object
	 */
	public function createComponentObject($componentName, T3_FLOW3_Component_Configuration $componentConfiguration);
			
}

?>