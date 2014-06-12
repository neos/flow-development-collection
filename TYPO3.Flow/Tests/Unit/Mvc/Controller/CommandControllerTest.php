<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Command Controller
 */
class CommandControllerTest extends UnitTestCase {

	/**
	 * @var CommandController
	 */
	protected $commandController;

	/**
	 * @var ReflectionService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockReflectionService;

	/**
	 * @var ConsoleOutput|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockConsoleOutput;

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\Flow\Cli\CommandController', array('resolveCommandMethodName', 'callCommandMethod'));

		$this->mockReflectionService = $this->getMockBuilder('TYPO3\Flow\Reflection\ReflectionService')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnValue(array()));
		$this->inject($this->commandController, 'reflectionService', $this->mockReflectionService);

		$this->mockConsoleOutput = $this->getMockBuilder('Symfony\Component\Console\Output\ConsoleOutput')->disableOriginalConstructor()->getMock();
		$this->inject($this->commandController, 'output', $this->mockConsoleOutput);
	}


	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException
	 */
	public function processRequestThrowsExceptionIfGivenRequestIsNoCliRequest() {
		$mockRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\RequestInterface')->getMock();
		$mockResponse = $this->getMockBuilder('TYPO3\Flow\Mvc\ResponseInterface')->getMock();

		$this->commandController->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function processRequestMarksRequestDispatched() {
		$mockRequest = $this->getMockBuilder('TYPO3\Flow\Cli\Request')->disableOriginalConstructor()->getMock();
		$mockResponse = $this->getMockBuilder('TYPO3\Flow\Mvc\ResponseInterface')->getMock();

		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$this->commandController->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function processRequestResetsCommandMethodArguments() {
		$mockRequest = $this->getMockBuilder('TYPO3\Flow\Cli\Request')->disableOriginalConstructor()->getMock();
		$mockResponse = $this->getMockBuilder('TYPO3\Flow\Mvc\ResponseInterface')->getMock();

		$mockArguments = new Arguments();
		$mockArguments->addNewArgument('foo');
		$this->inject($this->commandController, 'arguments', $mockArguments);

		$this->assertCount(1, $this->commandController->_get('arguments'));
		$this->commandController->processRequest($mockRequest, $mockResponse);
		$this->assertCount(0, $this->commandController->_get('arguments'));
	}

	/**
	 * @test
	 */
	public function outputWritesGivenStringToTheConsoleOutput() {
		$this->mockConsoleOutput->expects($this->once())->method('write')->with('some text');
		$this->commandController->_call('output', 'some text');
	}

	/**
	 * @test
	 */
	public function outputReplacesArgumentsInGivenString() {
		$this->mockConsoleOutput->expects($this->once())->method('write')->with('some text');
		$this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
	}

	/**
	 * @test
	 */
	public function outputLineAppendsGivenStringAndNewlineToTheResponseContent() {
		$this->mockConsoleOutput->expects($this->once())->method('write')->with('some text' . PHP_EOL);
		$this->commandController->_call('outputLine', 'some text');
	}
}
