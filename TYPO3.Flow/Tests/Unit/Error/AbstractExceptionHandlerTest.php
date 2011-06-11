<?php
namespace F3\FLOW3\Tests\Unit\Error;

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
 * Testcase for the Abstract Exception Handler
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractExceptionHandlerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleExceptionLogsInformationAboutTheExceptionInTheSystemLog() {
		$exception = new \Exception('The Message', 12345);

		$mockSystemLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');
		$mockSystemLogger->expects($this->once())->method('logException')->with($exception);

		$exceptionHandler = $this->getMockForAbstractClass('F3\FLOW3\Error\AbstractExceptionHandler', array(), '', FALSE);
		$exceptionHandler->injectSystemLogger($mockSystemLogger);
		$exceptionHandler->handleException($exception);
	}
}

?>