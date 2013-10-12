<?php
namespace TYPO3\Eel\Tests\Unit\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Test fixture object
 */
class TestObject implements ProtectedContextAwareInterface {

	/**
	 * @var string
	 */
	protected $property;

	/**
	 * @var boolean
	 */
	protected $booleanProperty;

	/**
	 * @var string
	 */
	protected $dynamicMethodName;

	/**
	 * @return string
	 */
	public function getProperty() {
		return $this->property;
	}

	/**
	 * @param $property
	 */
	public function setProperty($property) {
		$this->property = $property;
	}

	/**
	 * @param boolean $booleanProperty
	 */
	public function setBooleanProperty($booleanProperty) {
		$this->booleanProperty = $booleanProperty;
	}

	/**
	 * @return boolean
	 */
	public function isBooleanProperty() {
		return $this->booleanProperty;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function callMe($name) {
		return 'Hello, ' . $name . '!';
	}

	/**
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return $methodName === $this->dynamicMethodName;
	}

	/**
	 * @param string $dynamicMethodName
	 */
	public function setDynamicMethodName($dynamicMethodName) {
		$this->dynamicMethodName = $dynamicMethodName;
	}

}
