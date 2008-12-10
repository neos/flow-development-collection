<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * Implementation of the After Returning Advice.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\AfterReturningAdvice.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AfterReturningAdvice implements \F3\FLOW3\AOP\AdviceInterface {

	/**
	 * @var string Holds the name of the aspect object containing the advice
	 */
	protected $aspectObjectName;

	/**
	 * @var string Contains the name of the advice method
	 */
	protected $adviceMethodName;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface A reference to the Object Manager
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param string $aspectObjectName: Name of the aspect object containing the advice
	 * @param string $adviceMethodName: Name of the advice method
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager: A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($aspectObjectName, $adviceMethodName, \F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->aspectObjectName = $aspectObjectName;
		$this->adviceMethodName = $adviceMethodName;
		$this->objectManager = $objectManager;
	}

	/**
	 * Invokes the advice method
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface $joinPoint: The current join point which is passed to the advice method
	 * @return Result of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invoke(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$adviceObject = $this->objectManager->getObject($this->aspectObjectName);
		$methodName = $this->adviceMethodName;
		$adviceObject->$methodName($joinPoint);
	}

	/**
	 * Returns the aspect's object name which has been passed to the constructor
	 *
	 * @return string The object name of the aspect
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectObjectName() {
		return $this->aspectObjectName;
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