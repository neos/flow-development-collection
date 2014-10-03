<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Method;

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
use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Aop\Pointcut\PointcutFilterComposite;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * Contract for a privilege used to restrict method calls
 */
interface MethodPrivilegeInterface extends PrivilegeInterface {

	/**
	 * Returns TRUE, if this privilege covers the given join point
	 *
	 * @param JoinPointInterface $joinPoint
	 * @return bool
	 */
	public function matchesJoinpoint(JoinPointInterface $joinPoint);

	/**
	 * Returns TRUE, if this privilege covers the given method
	 *
	 * @param string $className
	 * @param string $methodName
	 * @return bool
	 */
	public function matchesMethod($className, $methodName);

	/**
	 * Returns the pointcut filter composite, matching all methods covered by this privilege
	 *
	 * @return PointcutFilterComposite
	 */
	public function getPointcutFilterComposite();
}