<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:$
 */

/**
 * Testcase for the request pattern resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_RequestPatternResolverTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable() {
		$requestPatternResolver = new F3_FLOW3_Security_RequestPatternResolver($this->componentManager);

		try {
			$requestPatternResolver->resolveRequestPatternClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Security_Exception_NoRequestPatternFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForAShortName() {
		$requestPatternResolver = new F3_FLOW3_Security_RequestPatternResolver($this->componentManager);
		$requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('URL');

		$this->assertEquals('F3_FLOW3_Security_RequestPattern_URL', $requestPatternClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassname() {
		$requestPatternResolver = new F3_FLOW3_Security_RequestPatternResolver($this->componentManager);
		$requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('F3_TestPackage_TestRequestPattern');

		$this->assertEquals('F3_TestPackage_TestRequestPattern', $requestPatternClass, 'The wrong classname has been resolved');
	}
}
?>