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
 * @ORM\Table(name="property_testentity_immutable")
 */
class TestEntityWithImmutableProperty {

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
	 * @param string $name Sets the immutable name property that has no setter.
	 */
	public function __construct($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
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
