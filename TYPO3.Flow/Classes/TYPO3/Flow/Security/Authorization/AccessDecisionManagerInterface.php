<?php
namespace TYPO3\Flow\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for an access decision manager.
 *
 */
interface AccessDecisionManagerInterface {

	/**
	 * Decides if access should be granted on the given object in the current security context
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The joinpoint to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint);

	/**
	 * Decides if access should be granted on the given resource in the current security context
	 *
	 * @param string $resource The resource to decide on
	 * @return void
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException If access is not granted
	 */
	public function decideOnResource($resource);

	/**
	 * Returns TRUE if access is granted on the given resource in the current security context
	 *
	 * @param string $resource The resource to decide on
	 * @return boolean TRUE if access is granted, FALSE otherwise
	 */
	public function hasAccessToResource($resource);
}
