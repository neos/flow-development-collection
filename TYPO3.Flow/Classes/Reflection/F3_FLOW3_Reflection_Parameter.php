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
 * @subpackage Reflection
 * @version $Id:F3_FLOW3_Reflection_Property.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Extended version of the ReflectionParameter
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:F3_FLOW3_Reflection_Property.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Reflection_Parameter extends ReflectionParameter {

	/**
	 * The constructor, initializes the reflection parameter
	 *
	 * @param  string $functionName: Name of the function
	 * @param  string $propertyName: Name of the property to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($function, $parameterName) {
		parent::__construct($function, $parameterName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return F3_FLOW3_Reflection_Class The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new F3_FLOW3_Reflection_Class(parent::getDeclaringClass()->getName());
	}

	/**
	 * Returns the parameter class
	 *
	 * @return F3_FLOW3_Reflection_Class The parameter class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClass() {
		$class = parent::getClass();
		return (is_object($class)) ? new F3_FLOW3_Reflection_Class(parent::getClass()->getName()) : NULL;
	}

}

?>