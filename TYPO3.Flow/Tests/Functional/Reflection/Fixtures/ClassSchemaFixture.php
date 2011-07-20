<?php
namespace TYPO3\FLOW3\Tests\Functional\Reflection\Fixtures;

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
 * A fixture for testing class schema building
 *
 * @scope prototype
 * @entity
 */
class ClassSchemaFixture {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $things = array();

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Some text with a @param annotation, which should not be parsed.
	 *
	 * @param string $name
	 * @return void
	 * @validate foo1 bar1
	 * @validate foo2 bar2
	 * @skipCsrf
	 */
	public function setName($name) {
		$this->name = $name;
	}

}
?>