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
 * Contract for a simple session.
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface SessionInterface {

	/**
	 * Starts the session, if is has not been already started
	 *
	 * @return void
	 */
	public function start();

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 */
	public function getID();

	/**
	 * Returns the contents (array) associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return array The contents associated with the given key
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 * @throws \F3\FLOW3\Session\Exception\NotExistingKey
	 */
	public function getData($key);

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param object $data The data to be stored
	 * @param string $key The key under which the data should be stored
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 */
	public function putData($key, $data);

	/**
	 * Explicitly writes (persists) and closes the session
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 */
	public function close();

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStarted
	 */
	public function destroy();

}

?>