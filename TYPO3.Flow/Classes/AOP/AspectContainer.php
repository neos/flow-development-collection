<?php
namespace TYPO3\FLOW3\AOP;

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
 * An aspect is represented by class tagged with the "aspect" annotation.
 * The aspect class may contain advices and pointcut declarations. Aspect
 * classes are wrapped by this Aspect Container.
 *
 * For each advice a pointcut expression (not declaration!) is required to define
 * when an advice should apply. The combination of advice and pointcut
 * expression is called "advisor".
 *
 * A pointcut declaration only contains a pointcut expression and is used to
 * make pointcut expressions reusable and combinable.
 *
 * An introduction declaration on the class level contains an interface name
 * and a pointcut expression and is used to introduce a new interface to the
 * target class.
 *
 * If used on a property an introduction contains a pointcut expression and is
 * used to introduce the annotated property into the target class.
 *
 * @scope prototype
 * @proxy disable
 */
class AspectContainer {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * An array of \TYPO3\FLOW3\AOP\Advisor objects
	 * @var array
	 */
	protected $advisors = array();

	/**
	 * An array of \TYPO3\FLOW3\AOP\InterfaceIntroduction objects
	 * @var array
	 */
	protected $interfaceIntroductions = array();

	/**
	 * An array of \TYPO3\FLOW3\AOP\PropertyIntroduction objects
	 * @var array
	 */
	protected $propertyIntroductions = array();

	/**
	 * An array of explicitly declared \TYPO3\FLOW3\Pointcut objects
	 * @var array
	 */
	protected $pointcuts = array();

	/**
	 * The constructor
	 *
	 * @param string $className Name of the aspect class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name of the aspect class
	 *
	 * @return string Name of the aspect class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Returns the advisors which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\FLOW3\AOP\Advisor objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvisors() {
		return $this->advisors;
	}

	/**
	 * Returns the interface introductions which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\FLOW3\AOP\InterfaceIntroduction objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfaceIntroductions() {
		return $this->interfaceIntroductions;
	}

	/**
	 * Returns the property introductions which were defined in the aspect
	 *
	 * @return array Array of \TYPO3\FLOW3\AOP\PropertyIntroduction objects
	 */
	public function getPropertyIntroductions() {
		return $this->propertyIntroductions;
	}

	/**
	 * Returns the pointcuts which were declared in the aspect. This
	 * does not contain the pointcuts which were made out of the pointcut
	 * expressions for the advisors!
	 *
	 * @return array Array of \TYPO3\FLOW3\AOP\Pointcut\Pointcut objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcuts() {
		return $this->pointcuts;
	}

	/**
	 * Adds an advisor to this aspect container
	 *
	 * @param \TYPO3\FLOW3\AOP\Advisor $advisor The advisor to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addAdvisor(\TYPO3\FLOW3\AOP\Advisor $advisor) {
		$this->advisors[] = $advisor;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param \TYPO3\FLOW3\AOP\InterfaceIntroduction $introduction
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addInterfaceIntroduction(\TYPO3\FLOW3\AOP\InterfaceIntroduction $introduction) {
		$this->interfaceIntroductions[] = $introduction;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param \TYPO3\FLOW3\AOP\PropertyIntroduction $introduction
	 * @return void
	 */
	public function addPropertyIntroduction(\TYPO3\FLOW3\AOP\PropertyIntroduction $introduction) {
		$this->propertyIntroductions[] = $introduction;
	}

	/**
	 * Adds a pointcut (from a pointcut declaration) to this aspect container
	 *
	 * @param \TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut The poincut to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addPointcut(\TYPO3\FLOW3\AOP\Pointcut\Pointcut $pointcut) {
		$this->pointcuts[] = $pointcut;
	}
}

?>