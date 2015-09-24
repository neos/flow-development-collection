<?php
namespace TYPO3\Flow\Tests\Unit\Security\Policy;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the policy expression parser
 *
 */
class PolicyExpressionParserTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @expectedException \TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException
     */
    public function parseMethodThrowsAnExceptionIfAnotherPrivilegeTargetIsReferencedInAnExpression()
    {
        $parser = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodTargetExpressionParser::class, array('parseDesignatorMethod'));
        $parser->parse('method(TYPO3\TestPackage\BasicClass->setSomeProperty()) || privilegeTarget2', 'FunctionTests');
    }
}
