<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Session;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Session
 * @version $Id$
 */

/**
 * Implementation of a transient session.
 *
 * This session behaves like any other session except that it only stores the
 * data during one request.
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TransientSession implements \F3\FLOW3\Session\SessionInterface {

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function start() {
		$this->sessionId = uniqid();
		$this->started = TRUE;
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getID() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStarted('The session has not been started yet.', 1218034659);
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The data associated with the given key or NULL
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStarted('The session has not been started yet.', 1218034660);
		return (array_key_exists($key, $this->data)) ? $this->data[$key] : NULL;
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param object $data The data to be stored
	 * @param string $key The key under which the data should be stored
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStarted('The session has not been started yet.', 1218034661);
		$this->data[$key] = $data;
	}

	/**
	 * Closes the session
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function close() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStarted('The session has not been started yet.', 1218034662);
		$this->started = FALSE;
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function destroy() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStarted('The session has not been started yet.', 1218034663);
		$this->data = array();
	}

}

?>