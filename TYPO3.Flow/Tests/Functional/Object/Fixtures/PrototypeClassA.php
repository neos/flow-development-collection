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
 * A class of scope prototype
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class PrototypeClassA implements PrototypeClassAishInterface {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA
	 */
	protected $singletonA;

	/**
	 * @var string
	 */
	protected $someProperty;

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA
	 * @return void
	 */
	public function injectSingletonA(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA) {
		$this->singletonA = $singletonA;
	}

	/**
	 * @return \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA The singleton class A
	 */
	public function getSingletonA() {
		return $this->singletonA;
	}

	/**
	 * @param string $someProperty The property value
	 * @return void
	 * @Flow\Session(autoStart=true)
	 */
	public function setSomeProperty($someProperty) {
		$this->someProperty = $someProperty;
	}

	/**
	 * @return string
	 */
	public function getSomeProperty() {
		return $this->someProperty;
	}
}
