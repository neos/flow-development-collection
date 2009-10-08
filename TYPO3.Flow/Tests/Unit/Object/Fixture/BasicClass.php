<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Object\Fixture;

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
 * Fixture class for unit tests mainly of the object manager
 *
 * Must not implement the *BasicClassInterface! (See comment there)
 *
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BasicClass {

	/**
	 * @var object Some injected dependency
	 */
	protected $firstDependency = NULL;

	/**
	 * @var object Some injected dependency
	 */
	protected $secondDependency = NULL;

	/**
	 * @var object Some property
	 */
	protected $someProperty = 42;

	/**
	 * @var boolean Flag which reveals if the initializeAfterPropertiesSet method has been called.
	 */
	protected $hasBeenInitialized = FALSE;

	/**
	 * Setter method for $firstDependency
	 *
	 * @param  object $value An object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFirstDependency($value) {
		$this->firstDependency = $value;
	}

	/**
	 * Getter method for $firstDependency
	 *
	 * @return mixed The value of $firstDependency
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFirstDependency() {
		return $this->firstDependency;
	}

	/**
	 * A method for setter injection of a dependency which is used
	 * for checking if injection of explicitly defined dependencies
	 * (without autowiring) works.
	 *
	 * @param  object $value An object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSecondDependency($value) {
		$this->secondDependency = $value;
	}

	/**
	 * This setter injection method is used to check if it is preferred
	 * over the setInjectOrSetMethod() method.
	 *
	 * @param  mixed $value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectInjectOrSetMethod($value) {
		$this->injectOrSetMethod = 'inject';
	}

	/**
	 * This setter injection method is used to check if it the
	 * injectInjectOrSetMethod() is  preferred over this method.
	 *
	 * @param  mixed $value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInjectOrSetMethod($value) {
		$this->injectOrSetMethod = 'set';
	}

	/**
	 * Getter method for $secondDependency
	 *
	 * @return mixed The value of $secondDependency
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSecondDependency() {
		return $this->secondDependency;
	}

	/**
	 * Setter method for $someProperty
	 *
	 * @param  mixed $value Some value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSomeProperty($value) {
		$this->someProperty = $value;
	}

	/**
	 * Getter method for $someProperty
	 *
	 * @return mixed The value of $someProperty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSomeProperty() {
		return $this->someProperty;
	}

	/**
	 * Throws an exception ...
	 *
	 * @param  string $exceptionType Class name of the exception to throw
	 * @param  mixed $parameter1 First parameter to pass to the exception constructor
	 * @param  mixed $parameter2 Second parameter to pass to the exception constructor
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwAnException($exceptionType, $parameter1 = NULL, $parameter2 = NULL) {
		throw new $exceptionType($parameter1, $parameter2);
	}

	/**
	 * The object initialization method which is called after properties have
	 * been injected.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeAfterPropertiesSet() {
		$this->hasBeenInitialized = ($this->firstDependency !== NULL) ? TRUE : 'yes, but no property was injected!';
	}

	/**
	 * Returns the hasBeenInitialized flag
	 *
	 * @return boolean Returns the hasBeenInitialized flag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasBeenInitialized() {
		return $this->hasBeenInitialized;
	}

	/**
	 * Some protected method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function someProtectedMethod() {

	}

	/**
	 * Some private method
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	private function somePrivateMethod() {

	}

	/**
	 * A great method which expects an array as an argument
	 *
	 * @param  array $someArray Some array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see    \F3\FLOW3\AOP\Builder\AdvicedMethodInterceptorBuilderTest
	 */
	public function methodWhichExpectsAnArrayArgument(array $someArray) {
	}

	/**
	 * A final public function
	 *
	 * @return void
	 * @see
	 */
	final public function someFinalMethod() {
		// the last public function I ever wrote
	}
}
?>