<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Tests::Persistence::Fixture;

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
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * A model fixture used for testing the persistence manager
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @entity
 */
class Entity3 implements F3::FLOW3::AOP::ProxyInterface {

	/**
	 * Just a normal string
	 *
	 * @var string
	 */
	public $someString;

	/**
	 * @var integer
	 */
	public $someInteger;

	/**
	 * Returns the name of the class this proxy extends.
	 *
	 * @return string Name of the target class
	 */
	public function AOPProxyGetProxyTargetClassName() {
		return 'F3::FLOW3::Tests::Persistence::Fixture::Entity3';
	}

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param F3::FLOW3::AOP::JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 */
	public function AOPProxyInvokeJoinPoint(F3::FLOW3::AOP::JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns the value of an arbitrary property.
	 * The method does not have to check if the property exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed Value of the property
	 */
	public function AOPProxyGetProperty($propertyName) {
		return $this->$propertyName;
	}

	/**
	 * Sets the value of an arbitrary property.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $propertyValue Value to set
	 * @return void
	 */
	public function AOPProxySetProperty($propertyName, $propertyValue) {

	}
}
?>