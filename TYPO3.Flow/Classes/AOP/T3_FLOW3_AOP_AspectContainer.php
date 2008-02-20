<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage AOP
 * @version $Id: $
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
 * @version $Id:T3_FLOW3_AOP_AspectContainer.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_AspectContainer {

	/**
	 * @var string Name of the aspect class
	 */
	protected $className;

	/**
	 * @var array An array of T3_FLOW3_AOP_Advisor objects
	 */
	protected $advisors = array();

	/**
	 * @var array An array of T3_FLOW3_AOP_Introduction objects
	 */
	protected $introductions = array();

	/**
	 * @var array An array of explicitly declared T3_FLOW3_Pointcut objects
	 */
	protected $pointcuts = array();

	/**
	 * The constructor
	 *
	 * @param  string			$className: Name of the aspect class
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Returns the name of the aspect class
	 *
	 * @return string		Name of the aspect class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Returns the advisors which were defined in the aspect
	 *
	 * @return array		Array of T3_FLOW3_AOP_Advisor objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvisors() {
		return $this->advisors;
	}

	/**
	 * Returns the introductions which were defined in the aspect
	 *
	 * @return array		Array of T3_FLOW3_AOP_Introduction objects
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
	 * @return array		Array of T3_FLOW3_AOP_Pointcut objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcuts() {
		return $this->pointcuts;
	}

	/**
	 * Adds an advisor to this aspect container
	 *
	 * @param  T3_FLOW3_AOP_AdvisorInterface		$advisor: The advisor to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addAdvisor(T3_FLOW3_AOP_AdvisorInterface $advisor) {
		$this->advisors[] = $advisor;
	}

	/**
	 * Adds an introduction declaration to this aspect container
	 *
	 * @param  T3_FLOW3_AOP_IntroductionInterface $introduction
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addIntroduction(T3_FLOW3_AOP_IntroductionInterface $introduction) {
		$this->introductions[] = $introduction;
	}

	/**
	 * Adds a pointcut (from a pointcut declaration) to this aspect container
	 *
	 * @param  T3_FLOW3_AOP_PointcutInterface	$pointcut: The poincut to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addPointcut(T3_FLOW3_AOP_PointcutInterface $pointcut) {
		$this->pointcuts[] = $pointcut;
	}
}

?>