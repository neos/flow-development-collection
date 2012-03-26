<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Command Controller
 */
class CommandControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Cli\CommandController
	 */
	protected $commandController;

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\FLOW3\Cli\CommandController', array('dummy'));
	}

	/**
	 * @test
	 */
	public function outputAppendsGivenStringToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', 'some text');
	}

	/**
	 * @test
	 */
	public function outputReplacesArgumentsInGivenString() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
	}

	/**
	 * @test
	 */
	public function outputLineAppendsGivenStringAndNewlineToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text' . PHP_EOL);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('outputLine', 'some text');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function quitThrowsStopActionException() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Cli\Response');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\StopActionException
	 */
	public function quitSetsResponseExitCode() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Cli\Response');
		$mockResponse->expects($this->once())->method('setExitCode')->with(123);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit', 123);
	}
}
?>