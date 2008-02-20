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
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In TYPO3 all advices are implemented as interceptors.
 * 
 * @package		Framework
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_AdviceInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see 		T3_FLOW3_AOP_InterceptorInterface
 */
interface T3_FLOW3_AOP_AdviceInterface {
	
	/**
	 * Constructor
	 *
	 * @param  string			$aspectComponentName: Name of the aspect component containing the advice
	 * @param  string			$adviceMethodName: Name of the advice method
	 * @param  T3_FLOW3_Component_ManagerInterface	$componentManager: A reference to the component manager
	 * @return void
	 */
	public function __construct($aspectComponentName, $adviceMethodName, T3_FLOW3_Component_ManagerInterface $componentManager);

	/**
	 * Invokes the advice method
	 *
	 * @param  T3_FLOW3_AOP_JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Optionally the result of the advice method
	 */
	public function invoke(T3_FLOW3_AOP_JoinPointInterface $joinPoint);
	
	/**
	 * Returns the aspect's component name which has been passed to the constructor
	 *
	 * @return string			The component name of the aspect
	 */
	public function getAspectComponentName();
	
	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string			The name of the advice method
	 */
	public function getAdviceMethodName();	
}

?>