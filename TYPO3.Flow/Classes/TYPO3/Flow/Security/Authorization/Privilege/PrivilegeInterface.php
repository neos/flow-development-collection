<?php
namespace TYPO3\Flow\Security\Authorization\Privilege;

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
use TYPO3\Flow\Cache\CacheAwareInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\Parameter\PrivilegeParameterInterface;
use TYPO3\Flow\Security\Exception\InvalidPrivilegeTypeException;

/**
 * Contract for a privilege
 */
interface PrivilegeInterface extends CacheAwareInterface {

	const ABSTAIN = 'abstain';
	const GRANT = 'grant';
	const DENY = 'deny';

	/**
	 * Note: We can't define constructors in interfaces, but this is assumed to exist in the concrete implementation!
	 *
	 * @param PrivilegeTarget $privilegeTarget
	 * @param string $matcher
	 * @param integer $permission One of the constants ABSTAIN, GRANT or DENY
	 * @param PrivilegeParameterInterface[] $parameters
	 */
	//public function __construct(PrivilegeTarget $privilegeTarget, $matcher, $permission, array $parameters) {

	/**
	 * This object is created very early so we can't rely on AOP for the property injection
	 *
	 * @param ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager);

	/**
	 * @return string
	 */
	public function getPermission();

	/**
	 * @return boolean
	 */
	public function isGranted();

	/**
	 * @return boolean
	 */
	public function isAbstained();

	/**
	 * @return boolean
	 */
	public function isDenied();

	/**
	 * Returns the related privilege target
	 *
	 * @return PrivilegeTarget
	 */
	public function getPrivilegeTarget();

	/**
	 * Unique name of the related privilege target (for example "TYPO3.Flow:PublicMethods")
	 *
	 * @return string
	 */
	public function getPrivilegeTargetIdentifier();

	/**
	 * A matcher string, describing the privilegeTarget (e.g. pointcut expression for methods or EEL expression for entities)
	 *
	 * @return string
	 */
	public function getMatcher();

	/**
	 * @return PrivilegeParameterInterface[]
	 */
	public function getParameters();

	/**
	 * @return boolean
	 */
	public function hasParameters();

	/**
	 * Returns TRUE, if this privilege covers the given subject
	 *
	 * @param PrivilegeSubjectInterface $subject
	 * @return boolean
	 * @throws InvalidPrivilegeTypeException if the given $subject is not supported by the privilege
	 */
	public function matchesSubject(PrivilegeSubjectInterface $subject);

}