<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Functional\AOP\Fixtures;

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
 * A target class for testing the AOP framework
 *
 * @scope prototype
 */
class TargetClass01 implements SayHelloInterface {

	/**
	 * @var \F3\FLOW3\Tests\Functional\AOP\Fixtures\Name
	 */
	protected $currentName;

	/**
	 * @var string
	 */
	public $constructorResult = '';

	/**
	 * @var integer
	 */
	public $initializeObjectCallCounter = 0;

	/**
	 *
	 */
	public function __construct() {
		$this->constructorResult .= 'AVRO RJ100';
	}

	/**
	 *
	 */
	public function initializeObject() {
		$this->initializeObjectCallCounter ++;
	}

	/**
	 * @return string
	 */
	public function sayHello() {
		return 'Hello';
	}

	/**
	 * @param boolean $throwException
	 * @return string
	 */
	public function sayHelloAndThrow($throwException) {
		if ($throwException) {
			throw new \Exception();
		}
		return 'Hello';
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function greet($name) {
		return 'Hello, ' . $name;
	}

	/**
	 * @param \F3\FLOW3\Tests\Functional\AOP\Fixtures\Name $name
	 * @return string
	 */
	public function greetObject(\F3\FLOW3\Tests\Functional\AOP\Fixtures\Name $name) {
		return 'Hello, ' . $name;
	}

	/**
	 * @param \SplObjectStorage $names
	 * @return string
	 */
	public function greetMany(\SplObjectStorage $names) {
		$greet = '';
		foreach ($names as $name) {
			$greet .= $name;
		}
		return 'Hello, ' . $greet;
	}

	/**
	 *
	 * @return string
	 */
	public function getCurrentName() {
		return $this->currentName;
	}

	/**
	 *
	 * @param \F3\FLOW3\Tests\Functional\AOP\Fixtures\Name $name
	 * @return void
	 */
	public function setCurrentName(\F3\FLOW3\Tests\Functional\AOP\Fixtures\Name $name = NULL) {
		$this->currentName = $name;
	}
}
?>