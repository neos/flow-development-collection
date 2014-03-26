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

	public function setUp() {
		$this->commandController = $this->getAccessibleMock('TYPO3\Flow\Cli\CommandController', array('resolveCommandMethodName', 'callCommandMethod'));

		$this->mockReflectionService = $this->getMockBuilder('TYPO3\Flow\Reflection\ReflectionService')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getMethodParameters')->will($this->returnValue(array()));
		$this->inject($this->commandController, 'reflectionService', $this->mockReflectionService);
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
	public function outputAppendsGivenStringToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', 'some text');
	}

	/**
	 * @test
	 */
	public function outputReplacesArgumentsInGivenString() {
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('output', '%2$s %1$s', array('text', 'some'));
	}

	/**
	 * @test
	 */
	public function outputLineAppendsGivenStringAndNewlineToTheResponseContent() {
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$mockResponse->expects($this->once())->method('appendContent')->with('some text' . PHP_EOL);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('outputLine', 'some text');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\StopActionException
	 */
	public function quitThrowsStopActionException() {
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\StopActionException
	 */
	public function quitSetsResponseExitCode() {
		$mockResponse = $this->getMock('TYPO3\Flow\Cli\Response');
		$mockResponse->expects($this->once())->method('setExitCode')->with(123);
		$this->commandController->_set('response', $mockResponse);
		$this->commandController->_call('quit', 123);
	}
}
