<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Security\Context as SecurityContext;

/**
 * Helper for security related information
 *
 */
class SecurityHelper implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var SecurityContext
	 */
	protected $securityContext;

	/**
	 * Get the account of the first authenticated token.
	 *
	 * @return \TYPO3\Flow\Security\Account|NULL
	 */
	public function getAccount() {
		if ($this->securityContext->canBeInitialized()) {
			return $this->securityContext->getAccount();
		}

		return NULL;
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}