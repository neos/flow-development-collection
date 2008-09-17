<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Error;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: $
 */

/**
 * Testcase for the alphanumeric validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id: $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ErrorTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorMessageCorrectly() {
		$errorMessage = 'The message';
		$error = new Error($errorMessage, 0);

		$this->assertEquals($errorMessage, $error->getErrorMessage());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theConstructorSetsTheErrorCodeCorrectly() {
		$errorCode = 123456789;
		$error = new Error('', $errorCode);

		$this->assertEquals($errorCode, $error->getErrorCode());
	}
}

?>