<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

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
 * @version $Id:F3::FLOW3::Reflection::PropertyReflection.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Extended version of the ReflectionParameter
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id:F3::FLOW3::Reflection::PropertyReflection.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ParameterReflection extends ::ReflectionParameter {

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
	 * @return F3::FLOW3::Reflection::ClassReflection The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new F3::FLOW3::Reflection::ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Returns the parameter class
	 *
	 * @return F3::FLOW3::Reflection::ClassReflection The parameter class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClass() {
		$class = parent::getClass();
		return (is_object($class)) ? new F3::FLOW3::Reflection::ClassReflection(parent::getClass()->getName()) : NULL;
	}

}

?>