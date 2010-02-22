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
 * A simple session based on PHP session functions.
 *
 * @version $Id: PhpSession.php -1   $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PhpSession implements \F3\FLOW3\Session\SessionInterface {

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var array
	 */
	protected $settings = NULL;

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
	 * Constructor.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct() {
		if (ini_get('session.auto_start') != 0) throw new \F3\FLOW3\Session\Exception\SessionAutostartIsEnabledException('PHP\'s session.auto_start must be disabled.', 1219848292);
	}

	/**
	 * Injects the FLOW3 settings, only the session settings are kept.
	 *
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Starts the session, if is has not been already started
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function start() {
		if ($this->started === FALSE) {
			if (empty($this->settings['session']['PHPSession']['savePath'])) {
				$sessionsPath = \F3\FLOW3\Utility\Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'Sessions'));
			} else {
				$sessionsPath = $this->settings['session']['PHPSession']['savePath'];
			}
			if (!file_exists($sessionsPath)) {
				mkdir($sessionsPath);
			}
			session_save_path($sessionsPath);
			session_start();
			$this->sessionId = session_id();
			$this->started = TRUE;
		}
	}

	/**
	 * Returns the current session ID.
	 *
	 * @return string The current session ID
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getID() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043307);
		return $this->sessionId;
	}

	/**
	 * Returns the data associated with the given key.
	 *
	 * @param string $key An identifier for the content stored in the session.
	 * @return mixed The contents associated with the given key
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getData($key) {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043308);
		return (array_key_exists($key, $_SESSION)) ? $_SESSION[$key] : NULL;
	}

	/**
	 * Returns TRUE if $key is available.
	 *
	 * @param string $key
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function hasKey($key) {
		return array_key_exists($key, $_SESSION);
	}

	/**
	 * Stores the given data under the given key in the session
	 *
	 * @param string $key The key under which the data should be stored
	 * @param mixed $data The data to be stored
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function putData($key, $data) {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043309);
		if (is_resource($data)) throw new \F3\FLOW3\Session\Exception\DataNotSerializeableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".', 1218475324);
		$_SESSION[$key] = $data;
	}

	/**
	 * Explicitly writes and closes the session
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function close() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043310);
		try {
			session_write_close();
		} catch (\Exception $exception) {
			throw new \F3\FLOW3\Session\Exception('The PHP session handler issued an error: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' in line ' . $exception->getLine() . '.', 1218474911);
		}
		unset($_SESSION);
	}

	/**
	 * Explicitly destroys all session data
	 *
	 * @return void
	 * @throws \F3\FLOW3\Session\Exception\SessionNotStartedException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function destroy() {
		if ($this->started !== TRUE) throw new \F3\FLOW3\Session\Exception\SessionNotStartedException('The session has not been started yet.', 1218043311);
		try {
			session_destroy();
		} catch (\Exception $exception) {
			throw new \F3\FLOW3\Session\Exception('The PHP session handler issued an error: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' in line ' . $exception->getLine() . '.', 1218474912);
		}
		unset($_SESSION);
	}
}

?>