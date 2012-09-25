<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

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
 * Fixture class for various unit tests (mainly the package- and object
 * manager)
 *
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