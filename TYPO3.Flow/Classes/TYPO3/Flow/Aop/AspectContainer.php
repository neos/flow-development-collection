<?php
namespace TYPO3\Flow\Aop;

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
 * An aspect is represented by class tagged with the "aspect" annotation.
 * The aspect class may contain advices and pointcut declarations. Aspect
 * classes are wrapped by this Aspect Container.
 *
 * For each advice a pointcut expression (not declaration!) is required to define
 * when an advice should apply. The combination of advice and pointcut
 * expression is called "advisor".
 *
 * A pointcut declaration only contains a pointcut expression and is used to
 * make pointcut expressions reusable and combinable.
 *
 * An introduction declaration on the class level contains an interface name
 * and a pointcut expression and is used to introduce a new interface to the
 * target class.
 *
 * If used on a property an introduction contains a pointcut expression and is
 * used to introduce the annotated property into the target class.
 *
 * @Flow\Proxy(false)
 */
class AspectContainer {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * An array of \TYPO3\Flow\Aop\Advisor objects
	 * @var array
	 */
	protected $advisors = array();

	/**
	 * An array of \TYPO3\Flow\Aop\InterfaceIntroduction objects
	 * @var array
	 */
	protected $interfaceIntroductions = array();

	/**
	 * An array of \TYPO3\Flow\Aop\PropertyIntroduction objects
	 * @var array
	 */
	protected $propertyIntroductions = array();

	/**
	 * An array of explicitly declared \TYPO3\Flow\Pointcut objects
	 * @var array
	 */
	protected $pointcuts = array();

	/**
	 * @var \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	protected $cachedTargetClassNameCandidates;

	/**
	 * The constructor
	 *
	 * @param string $className Name of the aspect class
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name of the aspect class
	 *
	 * @return string Name of the aspect class
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Returns the advisors which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\Flow\Aop\Advisor objects
	 */
	public function getAdvisors() {
		return $this->advisors;
	}

	/**
	 * Returns the interface introductions which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\Flow\Aop\InterfaceIntroduction objects
	 */
	public function getInterfaceIntroductions() {
		return $this->interfaceIntroductions;
	}

	/**
	 * Returns the property introductions which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\Flow\Aop\PropertyIntroduction objects
	 */
	public function getPropertyIntroductions() {
		return $this->propertyIntroductions;
	}

	/**
	 * Returns the pointcuts which were declared in the aspect. This
	 * does not contain the pointcuts which were made out of the pointcut
	 * expressions for the advisors!
	 *
	 * @return array Array of \TYPO3\Flow\Aop\Pointcut\Pointcut objects
	 */
	public function getPointcuts() {
		return $this->pointcuts;
	}

	/**
	 * Adds an advisor to this aspect container
	 *
	 * @param \TYPO3\Flow\Aop\Advisor $advisor The advisor to add
	 * @return void
	 */
	public function addAdvisor(\TYPO3\Flow\Aop\Advisor $advisor) {
		$this->advisors[] = $advisor;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param \TYPO3\Flow\Aop\InterfaceIntroduction $introduction
	 * @return void
	 */
	public function addInterfaceIntroduction(\TYPO3\Flow\Aop\InterfaceIntroduction $introduction) {
		$this->interfaceIntroductions[] = $introduction;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param \TYPO3\Flow\Aop\PropertyIntroduction $introduction
	 * @return void
	 */
	public function addPropertyIntroduction(\TYPO3\Flow\Aop\PropertyIntroduction $introduction) {
		$this->propertyIntroductions[] = $introduction;
	}

	/**
	 * Adds a pointcut (from a pointcut declaration) to this aspect container
	 *
	 * @param \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut The poincut to add
	 * @return void
	 */
	public function addPointcut(\TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut) {
		$this->pointcuts[] = $pointcut;
	}

	/**
	 * This method is used to optimize the matching process.
	 *
	 * @param \TYPO3\Flow\Aop\Builder\ClassNameIndex $classNameIndex
	 * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	public function reduceTargetClassNames(Builder\ClassNameIndex $classNameIndex) {
		$result = new Builder\ClassNameIndex();
		foreach ($this->advisors as $advisor) {
			$result->applyUnion($advisor->getPointcut()->reduceTargetClassNames($classNameIndex));
		}
		foreach ($this->interfaceIntroductions as $interfaceIntroduction) {
			$result->applyUnion($interfaceIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
		}
		foreach ($this->propertyIntroductions as $propertyIntroduction) {
			$result->applyUnion($propertyIntroduction->getPointcut()->reduceTargetClassNames($classNameIndex));
		}
		$this->cachedTargetClassNameCandidates = $result;
		return $result;
	}

	/**
	 * @return \TYPO3\Flow\Aop\Builder\ClassNameIndex
	 */
	public function getCachedTargetClassNameCandidates() {
		return $this->cachedTargetClassNameCandidates;
	}
}
