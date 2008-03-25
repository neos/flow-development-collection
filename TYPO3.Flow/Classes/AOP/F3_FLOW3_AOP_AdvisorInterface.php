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
 * @version $Id:F3_FLOW3_AOP_AdvisorInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface F3_FLOW3_AOP_AdvisorInterface {

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param  F3_FLOW3_AOP_AdviceInterface $advice: The advice to weave in
	 * @param  F3_FLOW3_AOP_PointcutInterface $pointcut: The pointcut where the advice should be inserted
	 */
	public function __construct(F3_FLOW3_AOP_AdviceInterface $advice, F3_FLOW3_AOP_PointcutInterface $pointcut);

	/**
	 * Returns the advisor's advice
	 *
	 * @return F3_FLOW3_AOP_AdviceInterface The advice
	 */
	public function getAdvice();

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return F3_FLOW3_AOP_Pointcut The pointcut
	 */
	public function getPointcut();

}
?>