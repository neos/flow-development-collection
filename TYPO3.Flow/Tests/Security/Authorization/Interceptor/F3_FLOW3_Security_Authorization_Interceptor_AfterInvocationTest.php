<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Security::Authorization::Interceptor;

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
 * Testcase for the policy enforcement interceptor
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class AfterInvocationTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted() {
		$mockSecurityContextHolder = $this->getMock('F3::FLOW3::Security::ContextHolderInterface');
		$mockAfterInvocationManager = $this->getMock('F3::FLOW3::Security::Authorization::AfterInvocationManagerInterface');

		$theResult = new ArrayObject(array('some' => 'stuff'));

		$interceptor = new F3::FLOW3::Security::Authorization::Interceptor::AfterInvocation($mockSecurityContextHolder, $mockAfterInvocationManager);
		$interceptor->setResult($theResult);
		$this->assertSame($theResult, $interceptor->invoke());
	}
}

?>