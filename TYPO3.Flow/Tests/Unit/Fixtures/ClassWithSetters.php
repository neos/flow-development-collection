<?php
namespace TYPO3\Flow\Fixtures;

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
 * A dummy class with setters for testing data mapping
 *
 */
class ClassWithSetters {

	/**
	 * @var mixed
	 */
	public $property1;

	/**
	 * @var mixed
	 */
	protected $property2;

	/**
	 * @var mixed
	 */
	public $property3;

	/**
	 * @var mixed
	 */
	public $property4;

	public function setProperty3($value) {
		$this->property3 = $value;
	}

	protected function setProperty4($value) {
		$this->property4 = $value;
	}

	public function getProperty2() {
		return $this->property2;
	}
}
