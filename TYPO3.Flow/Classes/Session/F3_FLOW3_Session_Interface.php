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
 * Contract for a simple session.
 *
 * @package FLOW3
 * @subpackage Session
 * @version $Id:$
 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_Session_Interface {

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
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function getID();

	/**
	 * Returns the contents (array) associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return array The contents associated with the given key
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 * @throws F3_FLOW3_Session_Exception_NotExistingKey
	 */
	public function getData($key);

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param object $data The data to be stored
	 * @param string $key The key under which the data should be stored
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function putData($key, $data);

	/**
	 * Explicitly writes (persists) and closes the session
	 *
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function close();

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws F3_FLOW3_Session_Exception_SessionNotStarted
	 */
	public function destroy();

}

?>