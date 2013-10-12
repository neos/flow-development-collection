<?php
namespace TYPO3\Flow\Security\Authorization\Interceptor;

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
 * This security interceptor always denys access.
 *
 * @Flow\Scope("singleton")
 */
class AccessDeny implements \TYPO3\Flow\Security\Authorization\InterceptorInterface {

	/**
	 * Invokes nothing, always throws an AccessDenied Exception.
	 *
	 * @return boolean Always returns FALSE
	 * @throws \TYPO3\Flow\Security\Exception\AccessDeniedException
	 */
	public function invoke() {
		throw new \TYPO3\Flow\Security\Exception\AccessDeniedException('You are not allowed to perform this action.', 1216919280);
	}
}
