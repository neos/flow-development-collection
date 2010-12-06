<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\AOP\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An aspect for testing the AOP framework
 *
 * @aspect
 */
class Aspect01 {

	/**
	 * @around method(public F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->sayHello())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function worldAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @around method(public F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->sayHelloAndThrow())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function throwWorldAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @around method(public F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greet(name === 'FLOW3'))
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function specialNameAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, me';
	}

	/**
	 * @around method(public F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetMany(names contains this.currentName))
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function manyNamesAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, special guest';
	}
}
?>