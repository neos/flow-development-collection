<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

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
 * A sub class of the abstract class
 *
 */
class SubClassOfAbstractClass extends AbstractClass {

	/**
	 * @param $foo
	 * @return string
	 */
	public function abstractMethod($foo) {
		return "foo: $foo";
	}

}
?>