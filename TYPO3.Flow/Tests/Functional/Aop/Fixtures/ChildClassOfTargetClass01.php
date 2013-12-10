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
 * A target class for testing the AOP framework
 */
class ChildClassOfTargetClass01 extends TargetClass01 {

	/**
	 * @return string
	 */
	public function sayHello() {
		return 'Greetings, I just wanted to say: ' . parent::sayHello();
	}

	/**
	 * @return string
	 */
	public function saySomethingSmart() {
		return parent::saySomethingSmart() . ' That was smart, eh?';
	}

	/**
	 * @return string
	 */
	public function sayWhatFlowIs() {
		return 'Flow is not';
	}

}
