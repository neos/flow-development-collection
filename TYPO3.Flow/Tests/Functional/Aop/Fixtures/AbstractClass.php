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
 * An abstract class with an abstract and a concrete method
 *
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