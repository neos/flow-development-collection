<?php
namespace TYPO3\FLOW3\Security\Authorization;

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
 * Contract for a security interceptor.
 *
 */
interface InterceptorInterface {

	/**
	 * Invokes the security interception (e.g. calls a \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface)
	 *
	 * @return boolean TRUE if the security checks was passed
	 */
	public function invoke();
}

?>