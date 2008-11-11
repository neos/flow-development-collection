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
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In FLOW3 all advices are implemented as interceptors.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::AOP::AdviceInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see F3::FLOW3::AOP::InterceptorInterface
 */
interface AdviceInterface {

	/**
	 * Constructor
	 *
	 * @param  string $aspectObjectName: Name of the aspect object containing the advice
	 * @param  string $adviceMethodName: Name of the advice method
	 * @param  F3::FLOW3::Object::ManagerInterface $objectManager: A reference to the object manager
	 * @return void
	 */
	public function __construct($aspectObjectName, $adviceMethodName, F3::FLOW3::Object::ManagerInterface $objectManager);

	/**
	 * Invokes the advice method
	 *
	 * @param  F3::FLOW3::AOP::JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Optionally the result of the advice method
	 */
	public function invoke(F3::FLOW3::AOP::JoinPointInterface $joinPoint);

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