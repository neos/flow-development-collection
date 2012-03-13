<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization\Interceptor;

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
 * Testcase for the policy enforcement interceptor
 *
 */
class AfterInvocationTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function invokeReturnsTheResultPreviouslySetBySetResultIfTheMethodIsNotIntercepted() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
		$mockAfterInvocationManager = $this->getMock('TYPO3\FLOW3\Security\Authorization\AfterInvocationManagerInterface');

		$theResult = new \ArrayObject(array('some' => 'stuff'));

		$interceptor = new \TYPO3\FLOW3\Security\Authorization\Interceptor\AfterInvocation($mockSecurityContext, $mockAfterInvocationManager);
		$interceptor->setResult($theResult);
		$this->assertSame($theResult, $interceptor->invoke());
	}
}

?>