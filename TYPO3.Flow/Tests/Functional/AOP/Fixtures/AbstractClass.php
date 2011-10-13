<?php
namespace TYPO3\FLOW3\Tests\Functional\AOP\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An abstract class with an abstract and a concrete method
 *
 * @FLOW3\Scope("prototype")
 */
abstract class AbstractClass {

	/**
	 * @param $foo
	 * @return string
	 */
	abstract public function abstractMethod($foo);

	/**
	 * @param $foo
	 * @return string
	 */
	public function concreteMethod($foo) {
		return "foo: $foo";
	}

}
?>