<?php
namespace TYPO3\FLOW3\MVC\CLI;

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
 * A CLI specific response implementation
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Response extends \TYPO3\FLOW3\MVC\Response {

	/**
	 * @var integer
	 */
	protected $exitCode = 0;

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return void
	 */
	public function appendContent($content) {
		$this->content .= $content . PHP_EOL;
	}

	/**
	 * Sets the numerical exit code which should be returned when exiting this application.
	 *
	 * @param integer $exitCode
	 * @return void
	 */
	public function setExitCode($exitCode) {
		if (!is_integer($exitCode)) {
			throw new \InvalidArgumentException(sprintf('Tried to set invalid exit code. The value must be integer, %s given.', gettype($exitCode)), 1312222064);
		}
		$this->exitCode = $exitCode;
	}

	/**
	 * Rets the numerical exit code which should be returned when exiting this application.
	 *
	 * @return integer
	 */
	public function getExitCode() {
		return $this->exitCode;
	}
}

?>