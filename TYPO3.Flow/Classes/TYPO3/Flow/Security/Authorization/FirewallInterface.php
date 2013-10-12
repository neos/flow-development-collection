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
 * Contract for firewall
 *
 */
interface FirewallInterface {

	/**
	 * Analyzes a request against the configured firewall rules and blocks
	 * any illegal request.
	 *
	 * @param \TYPO3\Flow\Mvc\ActionRequest $request The request to be analyzed
	 * @return void
	 */
	public function blockIllegalRequests(\TYPO3\Flow\Mvc\ActionRequest $request);
}
