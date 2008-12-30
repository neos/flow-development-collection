<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Object\Fixture;

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
 * Fixture class for various unit tests (mainly the package- and object
 * manager)
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class ClassWithOptionalArguments {

	public $argument1;
	public $argument2;
	public $argument3;

	/**
	 * Dummy constructor which accepts up to three arguments
	 *
	 * @param mixed $argument1
	 * @param mixed $argument2
	 * @param mixed $argument3
	 */
	public function __construct($argument1 = NULL, $argument2 = NULL, $argument3 = NULL) {
		$this->argument1 = $argument1;
		$this->argument2 = $argument2;
		$this->argument3 = $argument3;
	}
}
?>