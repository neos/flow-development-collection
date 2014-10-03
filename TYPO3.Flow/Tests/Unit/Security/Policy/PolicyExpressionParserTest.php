<?php
namespace TYPO3\Flow\Tests\Unit\Security\Policy;

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
 * Testcase for the policy expression parser
 *
 */
class PolicyExpressionParserTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
	 */
	public function parseMethodThrowsAnExceptionIfAnotherPrivilegeTargetIsReferencedInAnExpression() {
		$parser = $this->getMock('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodTargetExpressionParser', array('parseDesignatorMethod'));
		$parser->parse('method(TYPO3\TestPackage\BasicClass->setSomeProperty()) || privilegeTarget2', 'FunctionTests');
	}
}
