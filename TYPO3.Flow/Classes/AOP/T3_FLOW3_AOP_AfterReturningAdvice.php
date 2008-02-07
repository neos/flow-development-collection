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
 * Implementation of the After Returning Advice.
 *   
 * @package		FLOW3
 * @subpackage	AOP
 * @version 	$Id:T3_FLOW3_AOP_AfterReturningAdvice.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_AfterReturningAdvice implements T3_FLOW3_AOP_AdviceInterface{

	/**
	 * @var string Holds the name of the aspect component containing the advice
	 */
	protected $aspectComponentName;
	
	/**
	 * @var string Contains the name of the advice method
	 */
	protected $adviceMethodName;
	
	/**
	 * @var T3_FLOW3_Component_ManagerInterface A reference to the Component Manager
	 */
	protected $componentManager;
	
	/**
	 * Constructor
	 *
	 * @param  string			$aspectComponentName: Name of the aspect component containing the advice
	 * @param  string			$adviceMethodName: Name of the advice method
	 * @param  T3_FLOW3_Component_ManagerInterface $componentManager: A reference to the component manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($aspectComponentName, $adviceMethodName, T3_FLOW3_Component_ManagerInterface $componentManager) {
		$this->aspectComponentName = $aspectComponentName;
		$this->adviceMethodName = $adviceMethodName;
		$this->componentManager = $componentManager;
	}
	
	/**
	 * Invokes the advice method
	 *
	 * @param  T3_FLOW3_AOP_JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Result of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invoke(T3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		$adviceObject = $this->componentManager->getComponent($this->aspectComponentName);
		$methodName = $this->adviceMethodName;
		$adviceObject->$methodName($joinPoint);
	}
	
	/**
	 * Returns the aspect's component name which has been passed to the constructor
	 *
	 * @return string			The component name of the aspect
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectComponentName() {
		return $this->aspectComponentName;
	}
	
	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string			The name of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdviceMethodName() {
		return $this->adviceMethodName;
	}
}

?>