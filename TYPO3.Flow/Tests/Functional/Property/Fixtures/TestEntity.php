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

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A simple entity for PropertyMapper test
 *
 * @Flow\Entity
 * @ORM\Table(name="property_testentity")
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

	/**
	 * Set the age by specifying the year of birth
	 *
	 * For test purposes the reference year is hard-coded to 2013.
	 *
	 * @param integer $yearOfBirth
	 */
	public function setYearOfBirth($yearOfBirth) {
		$this->setAge(2013 - $yearOfBirth);
	}

}
