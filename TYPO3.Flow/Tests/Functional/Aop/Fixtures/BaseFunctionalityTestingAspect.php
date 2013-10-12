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

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect for testing the basic functionality of the AOP framework
 *
 * @Flow\Introduce("class(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)", interfaceName="TYPO3\Flow\Tests\Functional\Aop\Fixtures\Introduced01Interface")
 * @Flow\Aspect
 */
class BaseFunctionalityTestingAspect {

	/**
	 * @Flow\Introduce("class(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)")
	 * @var string
	 */
	protected $introducedProtectedProperty;

	/**
	 * @Flow\Introduce("class(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass03)")
	 * @var array
	 */
	public $introducedPublicProperty;

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->__construct())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function lousyConstructorAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$joinPoint->getAdviceChain()->proceed($joinPoint);
		$joinPoint->getProxy()->constructorResult .= ' is lousier than A-380';
	}

	/**
	 * @Flow\Around("within(TYPO3\Flow\Tests\Functional\Aop\Fixtures\SayHelloInterface) && method(.*->sayHello())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function worldAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @Flow\Around("within(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01) && method(.*->sayWhatFlowIs())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function rocketScienceAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' Rocket Science';
	}

	/**
	 * @Flow\Around("within(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01) && method(.*->someStaticMethod())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function tryToWrapStaticMethodAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'failed';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->saySomethingSmart())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function somethingSmartAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' For big twos and small fives!';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->sayHelloAndThrow())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function throwWorldAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' World';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet(name === 'Flow'))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function specialNameAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Hello, me';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function changeNameArgumentAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if ($joinPoint->getMethodArgument('name') === 'Andi') {
			$joinPoint->setMethodArgument('name', 'Robert');
		}
		return $joinPoint->getAdviceChain()->proceed($joinPoint);
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetMany(names contains this.currentName))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function manyNamesAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Hello, special guest';
	}

	/**
	 * @Flow\AfterReturning("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass02->publicTargetMethod())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAfterReturningAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$joinPoint->getProxy()->afterReturningAdviceWasInvoked = TRUE;
	}

	/**
	 * @Flow\Around("method(protected TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass02->protectedTargetMethod())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function anAdviceForAProtectedTargetMethod(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return $joinPoint->getAdviceChain()->proceed($joinPoint) . ' bar';
	}

	/**
	 * @Flow\Around("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetObject(name.name === 'TYPO3'))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function propertyOnMethodArgumentAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Hello, old friend';
	}

	/**
	 * @Flow\Around("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greetObject(name === this.currentName))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function thisOnMethodArgumentAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Hello, you';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass01->greet(name === current.testContext.nameOfTheWeek))")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function globalNameAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Hello, superstar';
	}

	/**
	 * @Flow\Around("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass03->introducedMethod01())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function introducedMethod01Implementation(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Implemented';
	}

	/**
	 * @Flow\Around("method(TYPO3\Flow\Tests\Functional\Aop\Fixtures\TargetClass03->introducedMethodWithArguments())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return string
	 */
	public function introducedMethodWithArgumentsImplementation(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		return 'Implemented';
	}

}
