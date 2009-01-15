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
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In FLOW3 all advices are implemented as interceptors.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 * @see \F3\FLOW3\AOP\InterceptorInterface
 */
interface AdviceInterface {

	/**
	 * Invokes the advice method
	 *
	 * @param  \F3\FLOW3\AOP\JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Optionally the result of the advice method
	 */
	public function invoke(\F3\FLOW3\AOP\JoinPointInterface $joinPoint);

	/**
	 * Returns the aspect's object name which has been passed to the constructor
	 *
	 * @return string The object name of the aspect
	 */
	public function getAspectObjectName();

	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string The name of the advice method
	 */
	public function getAdviceMethodName();
}
?>