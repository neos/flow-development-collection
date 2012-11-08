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

/**
 * Contract for a simple session.
 *
 */
interface SessionInterface {

	/**
	 * Tells if the session has been started already.
	 *
	 * @return boolean
	 */
	public function isStarted();

	/**
	 * Starts the session, if is has not been already started
	 *
	 * @return void
	 */
	public function start();

	/**
	 * Returns TRUE if there is a session that can be resumed. FALSE otherwise
	 *
	 * @return boolean
	 */
	public function canBeResumed();

	/**
	 * Resumes an existing session, if any.
	 *
	 * @return void
	 */
	public function resume();

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getId();

	/**
	 * Generates and propagates a new session ID and transfers all existing data
	 * to the new session.
	 *
	 * Renewing the session ID is one counter measure against Session Fixation Attacks.
	 *
	 * @return string The new session ID
	 */
	public function renewId();

	/**
	 * Returns the contents (array) associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return array The contents associated with the given key
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function getData($key);

	/**
	 * Returns TRUE if $key is available.
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function hasKey($key);

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param object $data The data to be stored
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function putData($key, $data);

	/**
	 * Explicitly writes (persists) and closes the session
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function close();

	/**
	 * Explicitly destroys all session data
	 *
	 * @param string $reason A reason for destroying the session – used by the LoggingAspect
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function destroy($reason = NULL);

	/**
	 * Explicitly destroy all session data of all sessions with one specific implementation.
	 *
	 * Note: The implementation of this method must work from the command line and
	 *       during compile time as it will be invoked by the typo3.flow:cache:flush
	 *       command. It is also not designed to be called from a regular runtime
	 *       context as it is unclear what happens to any possible active action.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return integer|NULL Optional: The number of sessions which have been destroyed
	 */
	static public function destroyAll(\TYPO3\Flow\Core\Bootstrap $bootstrap);

	/**
	 * Remove data of all sessions which are considered to be expired.
	 *
	 * @return integer The number of outdated entries removed or NULL if no such information could be determined
	 */
	public function collectGarbage();

}

?>