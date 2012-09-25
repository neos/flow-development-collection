<?php
namespace TYPO3\Flow\Tests\Persistence\Fixture\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A model fixture used for testing the persistence manager
 *
 * @Flow\Entity
 */
class CleanEntity implements \TYPO3\Flow\Aop\ProxyInterface {

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
	 * @param \TYPO3\Flow\Aop\JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 */
	public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {

	}

	/**
	 * Returns TRUE as this is a DirtyEntity
	 *
	 * @return boolean
	 */
	public function Flow_Persistence_isDirty() {
		return FALSE;
	}

	/**
	 * Dummy method for mock creation
	 * @return void
	 */
	public function Flow_Persistence_memorizeCleanState($propertyName = NULL) {}

	/**
	 * A stub to satisfy the Flow Proxy Interface
	 */
	public function __wakeup() {}

}
?>