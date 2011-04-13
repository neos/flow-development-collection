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
 * An aspect for testing the basic functionality of the AOP framework
 *
 * @introduce F3\FLOW3\Tests\Functional\AOP\Fixtures\Introduced01Interface, class(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
 * @aspect
 */
class BaseFunctionalityTestingAspect {

	/**
	 * @introduce class(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
	 * @var string
	 * @foo bar
	 */
	protected $introducedProtectedProperty;

	/**
	 * @introduce class(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
	 * @var array
	 */
	public $introducedPublicProperty;

	/**
	 * @around method(public F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->__construct())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function lousyConstructorAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);
		$joinPoint->getProxy()->constructorResult .= ' is lousier than A-380';
	}

	/**
	 * @around within(F3\FLOW3\Tests\Functional\AOP\Fixtures\SayHelloInterface) && method(.*->sayHello())
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

	/**
	 * @afterreturning method(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass02->publicTargetMethod())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAfterReturningAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->afterReturningAdviceWasInvoked = TRUE;
	}

	/**
	 * @around method(protected F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass02->protectedTargetMethod())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAdviceForAProtectedTargetMethod(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' bar';
	}

	/**
	 * @around method(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetObject(name.name === 'TYPO3'))
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function propertyOnMethodArgumentAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, old friend';
	}

	/**
	 * @around method(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetObject(name === this.currentName))
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function thisOnMethodArgumentAdvice(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, you';
	}

	/**
	 * @around method(F3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03->introducedMethod01())
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function introducedMethod01Implementation(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Implemented';
	}

}
?>