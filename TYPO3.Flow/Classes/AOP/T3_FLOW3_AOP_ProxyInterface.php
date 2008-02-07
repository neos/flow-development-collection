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
 * Contract and marker interface for the AOP Proxy classes
 * 
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_ProxyInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author		Robert Lemke <robert@typo3.org>
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface T3_FLOW3_AOP_ProxyInterface {
	
	/**
	 * Invokes the joinpoint - calls the target methods.
	 * 
	 * @param  T3_FLOW3_AOP_JoinPointInterface: The join point
	 * @return mixed                           Result of the target (ie. original) method
	 */
	public function invokeJoinPoint(T3_FLOW3_AOP_JoinPointInterface $joinPoint);
}

?>