<?php
namespace TYPO3\FLOW3\Session;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Implementation of a transient session.
 *
 * This session behaves like any other session except that it only stores the
 * data during one request.
 *
 * @FLOW3\Scope("singleton")
 */
class TransientSession implements \TYPO3\FLOW3\Session\SessionInterface {

	/**
	 * The session Id
	 *
	 * @var string
	 */
	protected $sessionId;

	/**
	 * If this session has been started
	 *
	 * @var boolean
	 */
	protected $started = FALSE;

	/**
	 * The session data
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Tells if the session has been started already.
	 *
	 * @return boolean
	 */
	public function isStarted() {
		return $this->started;
	}

	/**
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 */
	public function start() {
		$this->sessionId = uniqid();
		$this->started = TRUE;
	}

	/**
	 * Returns TRUE if there is a session that can be resumed. FALSE otherwise
	 *
	 * @return boolean
	 */
	public function canBeResumed() {
		return TRUE;
	}

	/**
	 * Resumes an existing session, if any.
	 *
	 * @return void
	 */
	public function resume() {
		if ($this->started === FALSE) {
			$this->start();
		}
	}

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * @return string The new session ID
	 */
	public function renewId() {
		$this->sessionId = uniqid();
		return $this->sessionId;
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function getId() {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034659);
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The data associated with the given key or NULL
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034660);
		return (array_key_exists($key, $this->data)) ? $this->data[$key] : NULL;
	}

	/**
	 * Returns TRUE if $key is available.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasKey($key) {
		return array_key_exists($key, $this->data);
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param object $data The data to be stored
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034661);
		$this->data[$key] = $data;
	}

	/**
	 * Closes the session
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function close() {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034662);
		$this->started = FALSE;
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @param string $reason A reason for destroying the session – used by the LoggingAspect
	 * @return void
	 * @throws \TYPO3\FLOW3\Session\Exception
	 * @throws \TYPO3\FLOW3\Session\Exception\SessionNotStartedException
	 */
	public function destroy($reason = NULL) {
		if ($this->started !== TRUE) throw new \TYPO3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218034663);
		$this->data = array();
	}

}

?>