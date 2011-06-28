<?php
namespace TYPO3\FLOW3\AOP\Advice;

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
 * Base class for Advices.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class AbstractAdvice implements \TYPO3\FLOW3\AOP\Advice\AdviceInterface {

	/**
	 * Holds the name of the aspect object containing the advice
	 * @var string
	 */
	protected $aspectObjectName;

	/**
	 * Contains the name of the advice method
	 * @var string
	 */
	protected $adviceMethodName;

	/**
	 * A reference to the Object Manager
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Runtime evaluations definition array
	 * @var array
	 */
	protected $runtimeEvaluationsDefinition;

	/**
	 * Runtime evaluations function
	 * @var \Closure
	 */
	protected $runtimeEvaluator;

	/**
	 * Constructor
	 *
	 * @param string $aspectObjectName Name of the aspect object containing the advice
	 * @param string $adviceMethodName Name of the advice method
	 * @param \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager Only require if a runtime evaluations function is specified
	 * @param \Closure $runtimeEvaluator Runtime evaluations function
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($aspectObjectName, $adviceMethodName, \TYPO3\FLOW3\Object\ObjectManagerInterface $objectManager = NULL, \Closure $runtimeEvaluator = NULL) {
		$this->aspectObjectName = $aspectObjectName;
		$this->adviceMethodName = $adviceMethodName;
		$this->objectManager = $objectManager;
		$this->runtimeEvaluator = $runtimeEvaluator;
	}

	/**
	 * Invokes the advice method
	 *
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point which is passed to the advice method
	 * @return Result of the advice method
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function invoke(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		if ($this->runtimeEvaluator !== NULL && $this->runtimeEvaluator->__invoke($joinPoint) === FALSE) return;

		$adviceObject = $this->objectManager->get($this->aspectObjectName);
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