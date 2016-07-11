<?php
namespace TYPO3\Flow\Tests\Unit\Security\Policy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
        $parser = $this->getMockBuilder(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodTargetExpressionParser::class)->setMethods(array('parseDesignatorMethod'))->getMock();
        $parser->parse('method(TYPO3\TestPackage\BasicClass->setSomeProperty()) || privilegeTarget2', 'FunctionTests');
    }
}
