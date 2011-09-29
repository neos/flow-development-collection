<?php
namespace TYPO3\FLOW3\Tests\Functional\Property\Fixtures;

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
 * A simple entity for PropertyMapper test
 *
 * @scope prototype
 * @entity
 * @Table(name="Property_TestEntity")
 */
class TestEntity {

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
	 * @var float
	 */
	protected $averageNumberOfKids;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	public function getAge() {
		return $this->age;
	}

	public function setAge($age) {
		$this->age = $age;
	}

	public function getAverageNumberOfKids() {
		return $this->averageNumberOfKids;
	}

	public function setAverageNumberOfKids($averageNumberOfKids) {
		$this->averageNumberOfKids = $averageNumberOfKids;
	}
}
?>