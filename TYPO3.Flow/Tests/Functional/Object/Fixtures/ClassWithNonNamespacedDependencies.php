<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

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
 * A class which references other dependencies from the same namespace.
 */
class ClassWithNonNamespacedDependencies {

	/**
	 * @Flow\Inject(lazy=FALSE)
	 * @var SingletonClassB
	 */
	protected $singletonClassB;

	/**
	 * @Flow\Inject(lazy=FALSE)
	 * @var SubNamespace\AnotherClass
	 */
	protected $classFromSubNamespace;

	/**
	 * @return SingletonClassB
	 */
	public function getSingletonClassB() {
		return $this->singletonClassB;
	}

	/**
	 * @return SubNamespace\AnotherClass
	 */
	public function getClassFromSubNamespace() {
		return $this->classFromSubNamespace;
	}
}
