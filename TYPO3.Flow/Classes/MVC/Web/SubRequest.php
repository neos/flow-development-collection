<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web;

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
 * Represents a web sub request (used in plugins for example)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class SubRequest extends \F3\FLOW3\MVC\Web\Request {

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $parentRequest;

	/**
	 * @var string
	 */
	protected $argumentNamespace = '';

	/**
	 * @param \F3\FLOW3\MVC\Web\Request $parentRequest
	 */
	public function __construct(\F3\FLOW3\MVC\Web\Request $parentRequest) {
		$this->parentRequest = $parentRequest;
	}

	/**
	 * @return \F3\FLOW3\MVC\Web\Request
	 */
	public function getParentRequest() {
		return $this->parentRequest;
	}

	/**
	 * @param string $argumentNamespace
	 * @return void
	 */
	public function setArgumentNamespace($argumentNamespace) {
		$this->argumentNamespace = $argumentNamespace;
	}

	/**
	 * @return string
	 */
	public function getArgumentNamespace() {
		return $this->argumentNamespace;
	}

}
?>
