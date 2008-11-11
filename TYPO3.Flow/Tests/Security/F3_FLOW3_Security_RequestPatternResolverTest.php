<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security;

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
 * @version $Id$
 */

/**
 * Testcase for the request pattern resolver
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestPatternResolverTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternClassThrowsAnExceptionIfNoRequestPatternIsAvailable() {
		$requestPatternResolver = new F3::FLOW3::Security::RequestPatternResolver($this->objectManager);

		try {
			$requestPatternResolver->resolveRequestPatternClass('IfSomeoneCreatesAClassNamedLikeThisTheFailingOfThisTestIsHisLeastProblem');
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Security::Exception::NoRequestPatternFound $exception) {

		}
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForAShortName() {
		$requestPatternResolver = new F3::FLOW3::Security::RequestPatternResolver($this->objectManager);
		$requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('URL');

		$this->assertEquals('F3::FLOW3::Security::RequestPattern::URL', $requestPatternClass, 'The wrong classname has been resolved');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function resolveRequestPatternReturnsTheCorrectRequestPatternForACompleteClassname() {
		$requestPatternResolver = new F3::FLOW3::Security::RequestPatternResolver($this->objectManager);
		$requestPatternClass = $requestPatternResolver->resolveRequestPatternClass('F3::TestPackage::TestRequestPattern');

		$this->assertEquals('F3::TestPackage::TestRequestPattern', $requestPatternClass, 'The wrong classname has been resolved');
	}
}
?>