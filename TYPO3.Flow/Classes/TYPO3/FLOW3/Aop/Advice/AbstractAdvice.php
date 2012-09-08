<?php
namespace TYPO3\FLOW3\Aop\Advice;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Base class for Advices.
 *
 */
class AbstractAdvice implements \TYPO3\FLOW3\Aop\Advice\AdviceInterface {

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
	 * A reference to the SingalSlot Dispatcher
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $dispatcher;

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
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point which is passed to the advice method
	 * @return mixed Result of the advice method
	 */
	public function invoke(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		if ($this->runtimeEvaluator !== NULL && $this->runtimeEvaluator->__invoke($joinPoint) === FALSE) {
			return;
		}

		$adviceObject = $this->objectManager->get($this->aspectObjectName);
		$methodName = $this->adviceMethodName;
		$adviceObject->$methodName($joinPoint);

		$this->emitAdviceInvoked($adviceObject, $methodName, $joinPoint);
	}

	/**
	 * Returns the aspect's object name which has been passed to the constructor
	 *
	 * @return string The object name of the aspect
	 */
	public function getAspectObjectName() {
		return $this->aspectObjectName;
	}

	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string The name of the advice method
	 */
	public function getAdviceMethodName() {
		return $this->adviceMethodName;
	}

	/**
	 * Emits a signal when an Advice is invoked
	 *
	 * The advice is not proxyable, so the signal is dispatched manually here.
	 *
	 * @param object $aspectObject
	 * @param string $methodName
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitAdviceInvoked($aspectObject, $methodName, $joinPoint) {
		if ($this->dispatcher === NULL) {
			$this->dispatcher = $this->objectManager->get('TYPO3\FLOW3\SignalSlot\Dispatcher');
		}

		$this->dispatcher->dispatch('TYPO3\FLOW3\Aop\Advice\AbstractAdvice', 'adviceInvoked', array($aspectObject, $methodName, $joinPoint));
	}
}

?>