<?php
namespace TYPO3\FLOW3\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * A target class for testing the AOP framework
 *
 */
class TargetClass02 {

	public $afterReturningAdviceWasInvoked = FALSE;

	/**
	 * @param mixed $foo
	 * @return mixed
	 */
	public function publicTargetMethod($foo) {
		return $this->protectedTargetMethod($foo);
	}

	/**
	 * @param $foo
	 * @return void
	 */
	protected function protectedTargetMethod($foo) {
		return $foo;
	}

}
?>