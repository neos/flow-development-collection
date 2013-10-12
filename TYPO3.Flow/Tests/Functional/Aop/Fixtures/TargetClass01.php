<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

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
 * A target class for testing the AOP framework
 *
 */
class TargetClass01 implements SayHelloInterface {

	/**
	 * @var \TYPO3\Flow\Tests\Functional\Aop\Fixtures\Name
	 */
	protected $currentName;

	/**
	 * @var string
	 */
	public $constructorResult = '';

	/**
	 * @var integer
	 */
	public $initializeObjectCallCounter = 0;

	/**
	 *
	 */
	public function __construct() {
		$this->constructorResult .= 'AVRO RJ100';
	}

	/**
	 *
	 */
	public function initializeObject() {
		$this->initializeObjectCallCounter ++;
	}

	/**
	 * @return string
	 */
	public function sayHello() {
		return 'Hello';
	}

	/**
	 * @return string
	 */
	public function sayWhatFlowIs() {
		return 'Flow is';
	}

	/**
	 * @return string
	 */
	public function saySomethingSmart() {
		return 'Two plus two makes five!';
	}

	/**
	 * @param boolean $throwException
	 * @return string
	 */
	public function sayHelloAndThrow($throwException) {
		if ($throwException) {
			throw new \Exception();
		}
		return 'Hello';
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function greet($name) {
		return 'Hello, ' . $name;
	}

	/**
	 * @param \TYPO3\Flow\Tests\Functional\Aop\Fixtures\Name $name
	 * @return string
	 */
	public function greetObject(\TYPO3\Flow\Tests\Functional\Aop\Fixtures\Name $name) {
		return 'Hello, ' . $name;
	}

	/**
	 * @param \SplObjectStorage $names
	 * @return string
	 */
	public function greetMany(\SplObjectStorage $names) {
		$greet = '';
		foreach ($names as $name) {
			$greet .= $name;
		}
		return 'Hello, ' . $greet;
	}

	/**
	 *
	 * @return string
	 */
	public function getCurrentName() {
		return $this->currentName;
	}

	/**
	 *
	 * @param \TYPO3\Flow\Tests\Functional\Aop\Fixtures\Name $name
	 * @return void
	 */
	public function setCurrentName(\TYPO3\Flow\Tests\Functional\Aop\Fixtures\Name $name = NULL) {
		$this->currentName = $name;
	}

	/**
	 * @return void
	 */
	static public function someStaticMethod() {
		return 'I won\'t take any advice';
	}
}
