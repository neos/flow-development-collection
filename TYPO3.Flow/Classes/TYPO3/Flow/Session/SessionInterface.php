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
 * Contract for a session.
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
	 * @param mixed $data The data to be stored
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 */
	public function putData($key, $data);

	/**
	 * Tags this session with the given tag.
	 *
	 * Note that third-party libraries might also tag your session. Therefore it is
	 * recommended to use namespaced tags such as "Acme-Demo-MySpecialTag".
	 *
	 * @param string $tag The tag – must match be a valid cache frontend tag
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function addTag($tag);

	/**
	 * Removes the specified tag from this session.
	 *
	 * @param string $tag The tag – must match be a valid cache frontend tag
	 * @return void
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function removeTag($tag);

	/**
	 * Returns the tags this session has been tagged with.
	 *
	 * @return array The tags or an empty array if there aren't any
	 * @throws \TYPO3\Flow\Session\Exception\SessionNotStartedException
	 * @api
	 */
	public function getTags();

	/**
	 * Updates the last activity time to "now".
	 *
	 * @return void
	 * @api
	 */
	public function touch();

	/**
	 * Returns the unix time stamp marking the last point in time this session has
	 * been in use.
	 *
	 * For the current (local) session, this method will always return the current
	 * time. For a remote session, the unix timestamp will be returned.
	 *
	 * @return integer unix timestamp
	 * @api
	 */
	public function getLastActivityTimestamp();

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
	 * Remove data of all sessions which are considered to be expired.
	 *
	 * @return integer The number of outdated entries removed or NULL if no such information could be determined
	 */
	public function collectGarbage();

}
