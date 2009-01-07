<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\CLI;

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
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\CLI\Request.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Represents a CLI request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\CLI\Request.php 467 2008-02-06 19:34:56Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @scope prototype
 */
class Request extends \F3\FLOW3\MVC\Request {

	/**
	 * Arguments given to a CLI request (i.e. anything not specifying command or options)
	 * @var array
	 */
	protected $CLIArguments = array();

	/**
	 * Sets the arguments given to this request.
	 *
	 * @param array $arguments
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setCLIArguments(array $arguments) {
		$this->CLIArguments = $arguments;
	}

	/**
	 * Returns the arguments given to the request.
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getCLIArguments() {
		return $this->CLIArguments;
	}
}
?>