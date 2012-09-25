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

/**
 * Testcase for the Abstract Exception Handler
 *
 */
class AbstractExceptionHandlerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function handleExceptionLogsInformationAboutTheExceptionInTheSystemLog() {
		$exception = new \Exception('The Message', 12345);

		$mockSystemLogger = $this->getMock('TYPO3\Flow\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->once())->method('logException')->with($exception);

		$exceptionHandler = $this->getMockForAbstractClass('TYPO3\Flow\Error\AbstractExceptionHandler', array(), '', FALSE);
		$exceptionHandler->injectSystemLogger($mockSystemLogger);
		$exceptionHandler->handleException($exception);
	}
}

?>