<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Property\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
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
	 * This ID is only for the ORM.
	 *
	 * @var integer
	 * @Id
	 * @GeneratedValue
	 */
	protected $artificialId;

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