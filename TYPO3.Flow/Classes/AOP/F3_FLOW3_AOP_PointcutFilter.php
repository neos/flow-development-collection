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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * A filter which refers to another pointcut.
 *
 * @package Framework
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_PointcutFilter.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_PointcutFilter implements F3_FLOW3_AOP_PointcutFilterInterface {

	/**
	 * @var string Name of the aspect class where the pointcut was declared
	 */
	protected $aspectClassName;

	/**
	 * @var string Name of the pointcut method
	 */
	protected $pointcutMethodName;

	/**
	 * @var F3_FLOW3_AOP_Framework A reference to the AOP Framework
	 */
	protected $aopFramework;

	/**
	 * The constructor - initializes the pointcut filter with the name of the pointcut we're refering to
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($aspectClassName, $pointcutMethodName, F3_FLOW3_AOP_Framework $aopFramework) {
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
		$this->aopFramework = $aopFramework;
	}

	/**
	 * Checks if the specified class and method matches with the pointcut
	 *
	 * @param ReflectionClass $class: The class to check against
	 * @param ReflectionMethod $method: The method to check against
	 * @param mixed $pointcutQueryIdentifier: Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class and method matches the pointcut, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches(ReflectionClass $class, ReflectionMethod $method, $pointcutQueryIdentifier) {
		$pointcut = $this->aopFramework->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		if ($pointcut === FALSE) throw new RuntimeException('No pointcut "' . $this->pointcutMethodName . '" found in aspect class "' . $this->aspectClassName . '" .', 1172223694);
		return $pointcut->matches($class, $method, $pointcutQueryIdentifier);
	}

	/**
	 * Prepares this pointcut filter for sleep
	 *
	 * @return void
	 */
	public function __sleep() {
		return array("\0*\0aspectClassName", "\0*\0pointcutMethodName");
	}

	/**
	 * Updates the reference to the AOP framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __wakeup() {
		if (isset($GLOBALS['FLOW3']['Cache']['Wakeup']['F3_FLOW3_AOP_Framework'])) {
			$this->aopFramework = $GLOBALS['FLOW3']['Cache']['Wakeup']['F3_FLOW3_AOP_Framework'];
		}
	}
}

?>