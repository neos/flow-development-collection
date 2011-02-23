<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\Object\Fixtures;

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
 * A class of scope prototype
 *
 * @scope prototype
 * @foo test annotation
 */
class PrototypeClassA implements PrototypeClassAishInterface {

	/**
	 * @var \F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA
	 */
	protected $singletonA;

	/**
	 * @var string
	 */
	protected $someProperty;

	/**
	 * @param \F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA
	 * @return void
	 */
	public function injectSingletonA(\F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA $singletonA) {
		$this->singletonA = $singletonA;
	}

	/**
	 * @return \F3\FLOW3\Tests\Functional\Object\Fixtures\SingletonClassA
	 */
	public function getSingletonA() {
		return $this->singletonA;
	}

	/**
	 * @param string $someProperty
	 * @return void
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
?>