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
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\Advisor.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Advisor implements \F3\FLOW3\AOP\AdvisorInterface {

	/**
	 * @var \F3\FLOW3\AOP\AdviceInterface: The advisor's advice
	 */
	protected $advice;

	/**
	 * @var \F3\FLOW3\AOP\PointcutInterface: The pointcut for the advice
	 */
	protected $pointcut;

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param \F3\FLOW3\AOP\AdviceInterface $advice: The advice to weave in
	 * @param \F3\FLOW3\AOP\PointcutInterface $pointcut: The pointcut where the advice should be inserted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\AOP\AdviceInterface $advice, \F3\FLOW3\AOP\PointcutInterface $pointcut) {
		$this->advice = $advice;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the advisor's advice
	 *
	 * @return \F3\FLOW3\AOP\AdviceInterface The advice
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvice() {
		return $this->advice;
	}

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return \F3\FLOW3\AOP\Pointcut The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}
}
?>