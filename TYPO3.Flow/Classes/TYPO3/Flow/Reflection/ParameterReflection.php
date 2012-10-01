<?php
namespace TYPO3\FLOW3\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Extended version of the ReflectionParameter
 *
 * @FLOW3\Proxy(false)
 */
class ParameterReflection extends \ReflectionParameter {

	/**
	 * @var string
	 */
	protected $parameterClassName;

	/**
	 * Returns the declaring class
	 *
	 * @return \TYPO3\FLOW3\Reflection\ClassReflection The declaring class
	 */
	public function getDeclaringClass() {
		return new ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Returns the parameter class
	 *
	 * @return \TYPO3\FLOW3\Reflection\ClassReflection The parameter class
	 */
	public function getClass() {
		try {
			$class = parent::getClass();
		} catch (\Exception $e) {
			return NULL;
		}

		return is_object($class) ? new ClassReflection($class->getName()) : NULL;
	}

}

?>