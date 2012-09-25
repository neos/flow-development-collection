<?php
namespace TYPO3\Flow\Aop;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Implementation of the interface introduction declaration.
 *
 */
class InterfaceIntroduction {

	/**
	 * Name of the aspect declaring this introduction
	 * @var string
	 */
	protected $declaringAspectClassName;

	/**
	 * Name of the introduced interface
	 * @var string
	 */
	protected $interfaceName;

	/**
	 * The pointcut this introduction applies to
	 * @var \TYPO3\Flow\Aop\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * Constructor
	 *
	 * @param string $declaringAspectClassName Name of the aspect containing the declaration for this introduction
	 * @param string $interfaceName Name of the interface to introduce
	 * @param \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut The pointcut for this introduction
	 */
	public function __construct($declaringAspectClassName, $interfaceName, \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut) {
		$this->declaringAspectClassName = $declaringAspectClassName;
		$this->interfaceName = $interfaceName;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the name of the introduced interface
	 *
	 * @return string Name of the introduced interface
	 */
	public function getInterfaceName() {
		return $this->interfaceName;
	}

	/**
	 * Returns the poincut this introduction applies to
	 *
	 * @return \TYPO3\Flow\Aop\Pointcut\Pointcut The pointcut
	 */
	public function getPointcut() {
		return $this->pointcut;
	}

	/**
	 * Returns the object name of the aspect which declared this introduction
	 *
	 * @return string The aspect object name
	 */
	public function getDeclaringAspectClassName() {
		return $this->declaringAspectClassName;
	}
}
?>