<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * @version $Id:F3::FLOW3::AOP::Advisor.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Advisor implements F3::FLOW3::AOP::AdvisorInterface {

	/**
	 * @var F3::FLOW3::AOP::AdviceInterface: The advisor's advice
	 */
	protected $advice;

	/**
	 * @var F3::FLOW3::AOP::PointcutInterface: The pointcut for the advice
	 */
	protected $pointcut;

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param F3::FLOW3::AOP::AdviceInterface $advice: The advice to weave in
	 * @param F3::FLOW3::AOP::PointcutInterface $pointcut: The pointcut where the advice should be inserted
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::AOP::AdviceInterface $advice, F3::FLOW3::AOP::PointcutInterface $pointcut) {
		$this->advice = $advice;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the advisor's advice
	 *
	 * @return F3::FLOW3::AOP::AdviceInterface The advice
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdvice() {
		return $this->advice;
	}

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return F3::FLOW3::AOP::Pointcut The pointcut
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcut() {
		return $this->pointcut;
	}
}
?>