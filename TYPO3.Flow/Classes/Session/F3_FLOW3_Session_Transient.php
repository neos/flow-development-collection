<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Session
 * @version $Id:$
 */

/**
 * Implementation of a transient session.
 *
 * This session behaves like any other session except that it only stores the
 * data during one request.
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Session_Transient implements F3_FLOW3_Session_Interface {

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
	 * Starts the session, if it has not been already started
	 *
	 * @return void
	 */
	public function start() {
		$this->sessionId = uniqid();
		$this->started = TRUE;
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function getID() {
		if ($this->started !== TRUE) throw new F3_FLOW3_Session_Exception_SessionNotStarted('The session has not been started yet.', 1218034659);
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The data associated with the given key or NULL
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new F3_FLOW3_Session_Exception_SessionNotStarted('The session has not been started yet.', 1218034660);
		return (key_exists($key, $this->data)) ? $this->data[$key] : NULL;
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param object $data The data to be stored
	 * @param string $key The key under which the data should be stored
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new F3_FLOW3_Session_Exception_SessionNotStarted('The session has not been started yet.', 1218034661);
		$this->data[$key] = $data;
	}

	/**
	 * Closes the session
	 *
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function close() {
		if ($this->started !== TRUE) throw new F3_FLOW3_Session_Exception_SessionNotStarted('The session has not been started yet.', 1218034662);
		$this->started = FALSE;
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function destroy() {
		if ($this->started !== TRUE) throw new F3_FLOW3_Session_Exception_SessionNotStarted('The session has not been started yet.', 1218034663);
		$this->data = array();
	}

}

?>