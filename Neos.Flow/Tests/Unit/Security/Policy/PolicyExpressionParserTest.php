<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Policy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\Exception\InvalidPointcutExpressionException;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodTargetExpressionParser;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the policy expression parser
 */
final class PolicyExpressionParserTest extends UnitTestCase
{
    #[Test]
    public function parseMethodThrowsAnExceptionIfAnotherPrivilegeTargetIsReferencedInAnExpression()
    {
        $this->expectException(InvalidPointcutExpressionException::class);
        $parser = $this->getMockBuilder(MethodTargetExpressionParser::class)->onlyMethods(['parseDesignatorMethod'])->getMock();
        $parser->parse('method(TYPO3\TestPackage\BasicClass->setSomeProperty()) || privilegeTarget2', 'FunctionTests');
    }
}
