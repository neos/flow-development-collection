<?php
namespace TYPO3\FLOW3\Aop;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract and marker interface for the AOP Proxy classes
 *
 */
interface ProxyInterface extends \TYPO3\FLOW3\Object\Proxy\ProxyInterface {

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The join point
	 * @return mixed Result of the target (ie. original) method
	 */
	public function FLOW3_Aop_Proxy_invokeJoinPoint(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint);

}

?>