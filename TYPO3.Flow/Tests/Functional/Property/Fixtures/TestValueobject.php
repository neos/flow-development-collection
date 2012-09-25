<?php
namespace TYPO3\Flow\Tests\Functional\Property\Fixtures;

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
 * A simple valueobject for PropertyMapper test
 *
 * @Flow\ValueObject
 */
class TestValueobject {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 *
	 * @var integer
	 */
	protected $age;

	/**
	 *
	 * @param string $name
	 * @param integer $age
	 */
	public function __construct($name, $age) {
		$this->name = $name;
		$this->age = $age;
	}

	/**
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 *
	 * @return integer
	 */
	public function getAge() {
		return $this->age;
	}

}
?>