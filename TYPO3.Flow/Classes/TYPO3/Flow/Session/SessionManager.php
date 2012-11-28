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
 * Session Manager
 *
 * @Flow\Scope("singleton")
 */
class SessionManager implements SessionManagerInterface {

	/**
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $currentSession;

	/**
	 * @var array
	 */
	protected $remoteSessions;

	/**
	 * Storage cache used by sessions
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * Returns the currently active session which stores session data for the
	 * current HTTP request on this local system.
	 *
	 * @return \TYPO3\Flow\Session\SessionInterface
	 * @api
	 */
	public function getCurrentSession() {
		if ($this->currentSession === NULL) {
			$this->currentSession = new Session();
		}
		return $this->currentSession;
	}

	/**
	 * Returns the specified session. If no session with the given identifier exists,
	 * NULL is returned.
	 *
	 * @param string $sessionIdentifier The session identifier
	 * @return \TYPO3\Flow\Session\Session
	 * @api
	 */
	public function getSession($sessionIdentifier) {
		if ($this->currentSession !== NULL && $this->currentSession->isStarted() && $this->currentSession->getId() === $sessionIdentifier) {
			return $this->currentSession;
		}
		if (isset($this->remoteSessions[$sessionIdentifier])) {
			return $this->remoteSessions[$sessionIdentifier];
		}
		if ($this->cache->has($sessionIdentifier)) {
			$sessionInfo = $this->cache->get($sessionIdentifier);
			$this->remoteSessions[$sessionIdentifier] = new Session($sessionIdentifier, $sessionInfo['storageIdentifier']);
			return $this->remoteSessions[$sessionIdentifier];
		}
	}

	/**
	 * Returns all active sessions, even remote ones.
	 *
	 * @return array<\TYPO3\Flow\Session\Session>
	 * @api
	 */
	public function getActiveSessions() {
		$activeSessions = array();
		foreach ($this->cache->getByTag('session') as $sessionIdentifier => $info) {
			$session = new Session($sessionIdentifier, $info['storageIdentifier']);
			$activeSessions[] = $session;
		}
		return $activeSessions;
	}
}

?>
