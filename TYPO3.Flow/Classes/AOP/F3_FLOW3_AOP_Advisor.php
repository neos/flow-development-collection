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
 * @version $Id$
 */

/**
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_Advisor.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_Advisor implements F3_FLOW3_AOP_AdvisorInterface {

	/**
	 * @var F3_FLOW3_AOP_AdviceInterface: The advisor's advice
	 */
	protected $advice;

	/**
	 * @var F3_FLOW3_AOP_PointcutInterface: The pointcut for the advice
	 */
	protected $pointcut;

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param F3_FLOW3_AOP_AdviceInterface $advice: The advice to weave in
	 * @param F3_FLOW3_AOP_PointcutInterface $pointcut: The pointcut where the advice should be inserted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_AOP_AdviceInterface $advice, F3_FLOW3_AOP_PointcutInterface $pointcut) {
		$this->advice = $advice;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the advisor's advice
	 *
	 * @return F3_FLOW3_AOP_AdviceInterface The advice
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvice() {
		return $this->advice;
	}

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return F3_FLOW3_AOP_Pointcut The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}
}
?>