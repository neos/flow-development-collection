<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

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
 * An introduction declaration contains an interface name and a pointcut expression
 * and is used to introduce a new interface to the target class.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class AspectContainer {

	/**
	 * @var string Name of the aspect class
	 */
	protected $className;

	/**
	 * @var array An array of \F3\FLOW3\AOP\Advisor objects
	 */
	protected $advisors = array();

	/**
	 * @var array An array of \F3\FLOW3\AOP\Introduction objects
	 */
	protected $introductions = array();

	/**
	 * @var array An array of explicitly declared \F3\FLOW3\Pointcut objects
	 */
	protected $pointcuts = array();

	/**
	 * The constructor
	 *
	 * @param  string $className: Name of the aspect class
	 * @return void
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
	 * @return array Array of \F3\FLOW3\AOP\Advisor objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvisors() {
		return $this->advisors;
	}

	/**
	 * Returns the introductions which were defined in the aspect
	 *
	 * @return array Array of \F3\FLOW3\AOP\Introduction objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getIntroductions() {
		return $this->introductions;
	}

	/**
	 * Returns the pointcuts which were declared in the aspect. This
	 * does not contain the pointcuts which were made out of the pointcut
	 * expressions for the advisors!
	 *
	 * @return array Array of \F3\FLOW3\AOP\Pointcut\Pointcut objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcuts() {
		return $this->pointcuts;
	}

	/**
	 * Adds an advisor to this aspect container
	 *
	 * @param \F3\FLOW3\AOP\Advisor $advisor: The advisor to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addAdvisor(\F3\FLOW3\AOP\Advisor $advisor) {
		$this->advisors[] = $advisor;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param \F3\FLOW3\AOP\Introduction $introduction
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addIntroduction(\F3\FLOW3\AOP\Introduction $introduction) {
		$this->introductions[] = $introduction;
	}

	/**
	 * Adds a pointcut (from a pointcut declaration) to this aspect container
	 *
	 * @param \F3\FLOW3\AOP\Pointcut\Pointcut $pointcut: The poincut to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addPointcut(\F3\FLOW3\AOP\Pointcut\Pointcut $pointcut) {
		$this->pointcuts[] = $pointcut;
	}
}

?>