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
 * A sub class of the abstract class
 *
 * @FLOW3\Scope("prototype")
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