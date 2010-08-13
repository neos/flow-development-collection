<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * This is a container for all Flash Messages. It is of scope session, thus, it is automatically persisted.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope session
 */
class FlashMessageContainer {

	/**
	 * The array of flash messages
	 * @var array<string>
	 */
	protected $flashMessages = array();

	/**
	 * Add another flash message.
	 *
	 * @param string $message
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function add($message) {
		if (!is_string($message)) throw new \InvalidArgumentException('The flash message must be string, ' . gettype($message) . ' given.', 1243258395);
		$this->flashMessages[] = $message;
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return array<string> An array of flash messages
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getAll() {
		return $this->flashMessages;
	}

	/**
	 * Reset all flash messages.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function flush() {
		$this->flashMessages = array();
	}

	/**
	 * Get all flash messages currently available and delete them afterwards.
	 *
	 * @return array<string>
	 * @api
	 */
	public function getAllAndFlush() {
		$flashMessages = $this->flashMessages;
		$this->flashMessages = array();
		return $flashMessages;
	}
}

?>