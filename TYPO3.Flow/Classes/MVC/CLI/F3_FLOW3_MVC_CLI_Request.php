<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\CLI;

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
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\CLI\Request.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Represents a CLI request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:\F3\FLOW3\MVC\CLI\Request.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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