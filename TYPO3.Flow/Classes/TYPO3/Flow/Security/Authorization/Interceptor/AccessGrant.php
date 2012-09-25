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
 * This security interceptor always grants access.
 *
 * @Flow\Scope("singleton")
 */
class AccessGrant implements \TYPO3\Flow\Security\Authorization\InterceptorInterface {

	/**
	 * Invokes nothing, always returns TRUE.
	 *
	 * @return boolean Always returns TRUE
	 */
	public function invoke() {
		return TRUE;
	}
}

?>