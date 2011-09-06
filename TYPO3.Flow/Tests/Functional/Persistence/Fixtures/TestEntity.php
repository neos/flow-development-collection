<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

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
 * A simple entity for persistence tests
 *
 * @scope prototype
 * @entity
 * @Table(name="Persistence_TestEntity")
 */
class TestEntity {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $arrayProperty = array();

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

	/**
	 * @param array $arrayProperty
	 * @return void
	 */
	public function setArrayProperty($arrayProperty) {
		$this->arrayProperty = $arrayProperty;
	}

	/**
	 * @return array
	 */
	public function getArrayProperty() {
		return $this->arrayProperty;
	}

}
?>