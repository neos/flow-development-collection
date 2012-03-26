<?php
namespace TYPO3\FLOW3\Tests\Persistence\Fixture\Model;

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
 * A model fixture used for testing the persistence manager
 *
 * @FLOW3\Entity
 */
class Entity3 implements \TYPO3\FLOW3\Aop\ProxyInterface {

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
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 */
	public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {

	}

	/**
	 * A stub to satisfy the FLOW3 Proxy Interface
	 */
	public function __wakeup() {}

}
?>