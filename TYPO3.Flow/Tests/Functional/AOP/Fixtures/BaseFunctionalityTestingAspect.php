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

/**
 * An aspect for testing the basic functionality of the AOP framework
 *
 * @introduce TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\Introduced01Interface, class(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
 * @aspect
 */
class BaseFunctionalityTestingAspect {

	/**
	 * @introduce class(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
	 * @var string
	 */
	protected $introducedProtectedProperty;

	/**
	 * @introduce class(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03)
	 * @var array
	 */
	public $introducedPublicProperty;

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->__construct())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function lousyConstructorAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);
		$joinPoint->getProxy()->constructorResult .= ' is lousier than A-380';
	}

	/**
	 * @around within(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\SayHelloInterface) && method(.*->sayHello())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function worldAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->saySomethingSmart())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function somethingSmartAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' For big twos and small fives!';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->sayHelloAndThrow())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function throwWorldAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greet(name === 'FLOW3'))
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function specialNameAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, me';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetMany(names contains this.currentName))
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function manyNamesAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, special guest';
	}

	/**
	 * @afterreturning method(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass02->publicTargetMethod())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAfterReturningAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->afterReturningAdviceWasInvoked = TRUE;
	}

	/**
	 * @around method(protected TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass02->protectedTargetMethod())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAdviceForAProtectedTargetMethod(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' bar';
	}

	/**
	 * @around method(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetObject(name.name === 'TYPO3'))
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function propertyOnMethodArgumentAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, old friend';
	}

	/**
	 * @around method(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greetObject(name === this.currentName))
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function thisOnMethodArgumentAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, you';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass01->greet(name === current.testContext.nameOfTheWeek))
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function globalNameAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Hello, superstar';
	}

	/**
	 * @around method(TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\TargetClass03->introducedMethod01())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function introducedMethod01Implementation(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		return 'Implemented';
	}

}
?>