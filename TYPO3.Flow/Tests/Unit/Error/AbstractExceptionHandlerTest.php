<?php
namespace TYPO3\Flow\Tests\Unit\Error;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use TYPO3\Flow\Error\AbstractExceptionHandler;
use TYPO3\Flow\Exception;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test case for the Abstract Exception Handler
 */
class AbstractExceptionHandlerTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function handleExceptionLogsInformationAboutTheExceptionInTheSystemLog() {
		$options = array(
			'defaultRenderingOptions' => array(
				'renderTechnicalDetails' => TRUE,
				'logException' => TRUE
			),
			'renderingGroups' => array()
		);

		$exception = new \Exception('The Message', 12345);

		$mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->once())->method('logException')->with($exception);

		$exceptionHandler = $this->getMockForAbstractClass('TYPO3\Flow\Error\AbstractExceptionHandler', array(), '', FALSE);
		/** @var AbstractExceptionHandler $exceptionHandler */
		$exceptionHandler->setOptions($options);
		$exceptionHandler->injectSystemLogger($mockSystemLogger);
		$exceptionHandler->handleException($exception);
	}

	/**
	 * @test
	 */
	public function handleExceptionDoesNotLogInformationAboutTheExceptionInTheSystemLogIfLogExceptionWasTurnedOff() {
		$options = array(
			'defaultRenderingOptions' => array(
				'renderTechnicalDetails' => TRUE,
				'logException' => TRUE
			),
			'renderingGroups' => array(
				'notFoundExceptions' => array(
					'matchingStatusCodes' => array(404),
					'options' => array(
						'logException' => FALSE,
						'templatePathAndFilename' => 'resource://TYPO3.Flow/Private/Templates/Error/Default.html',
						'variables' => array(
							'errorDescription' => 'Sorry, the page you requested was not found.'
						)

					)
				)
			)
		);

		/** @var Exception|\PHPUnit_Framework_MockObject_MockObject $exception */
		$exception = new NoMatchingRouteException();

		/** @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject $mockSystemLogger */
		$mockSystemLogger = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$mockSystemLogger->expects($this->never())->method('logException');

		$exceptionHandler = $this->getMockForAbstractClass('TYPO3\Flow\Error\AbstractExceptionHandler', array(), '', FALSE);
		/** @var AbstractExceptionHandler $exceptionHandler */
		$exceptionHandler->setOptions($options);
		$exceptionHandler->injectSystemLogger($mockSystemLogger);
		$exceptionHandler->handleException($exception);
	}
}
