<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture;

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
 * Dummy class for the Reflection tests
 *
 */
class DummyClassWithMethods {

	/**
	 * Some method
	 *
	 * @firsttag
	 * @secondtag a
	 * @secondtag b
	 * @param string $arg1 Argument 1 documentation
	 * @return void
	 */
	public function firstMethod($arg1, &$arg2, \stdClass $arg3, $arg4 = 'default') {

	}

	/**
	 * Some method
	 *
	 * @return void
	 */
	protected function secondMethod() {

	}
}
?>