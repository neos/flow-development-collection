<?php
namespace TYPO3\Flow\Session;

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
 * Interface for a session manager
 *
 * In order to stay compatible with future features and create more portable apps,
 * make sure to inject this interface instead of the concrete SessionManager
 * implementation.
 */
interface SessionManagerInterface {

	/**
	 * Returns the currently active session which stores session data for the
	 * current HTTP request on this local system.
	 *
	 * @return \TYPO3\Flow\Session\SessionInterface
	 */
	public function getCurrentSession();

	/**
	 * Returns the specified session. If no session with the given identifier exists,
	 * NULL is returned.
	 *
	 * @param string $sessionIdentifier The session identifier
	 * @return \TYPO3\Flow\Session\SessionInterface
	 */
	public function getSession($sessionIdentifier);

	/**
	 * Returns all active sessions, even remote ones.
	 *
	 * @return array<\TYPO3\Flow\Session\Session>
	 * @api
	 */
	public function getActiveSessions();

}
