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
 * Implementation of the Before Advice.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3_FLOW3_AOP_BeforeAdvice.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_AOP_BeforeAdvice implements F3_FLOW3_AOP_AdviceInterface {

	/**
	 * @var string Holds the name of the aspect component containing the advice
	 */
	protected $aspectComponentName;

	/**
	 * @var string Contains the name of the advice method
	 */
	protected $adviceMethodName;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface A reference to the Component Factory
	 */
	protected $componentFactory;

	/**
	 * Constructor
	 *
	 * @param string $aspectComponentName: Name of the aspect component containing the advice
	 * @param string $adviceMethodName: Name of the advice method
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory: A reference to the component factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($aspectComponentName, $adviceMethodName, F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->aspectComponentName = $aspectComponentName;
		$this->adviceMethodName = $adviceMethodName;
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Invokes the advice method
	 *
	 * @param F3_FLOW3_AOP_JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Result of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invoke(F3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		$adviceObject = $this->componentFactory->getComponent($this->aspectComponentName);
		$methodName = $this->adviceMethodName;
		$adviceObject->$methodName($joinPoint);
	}

	/**
	 * Returns the aspect's component name which has been passed to the constructor
	 *
	 * @return string The component name of the aspect
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectComponentName() {
		return $this->aspectComponentName;
	}

	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string The name of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAdviceMethodName() {
		return $this->adviceMethodName;
	}
}

?>