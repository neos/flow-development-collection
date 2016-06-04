<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for NumericNode
 *
 */
class NumericNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function renderReturnsProperIntegerGivenInConstructor()
    {
        $string = '1';
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\NumericNode($string);
        $this->assertEquals($node->evaluate($this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContext')), 1, 'The rendered value of a numeric node does not match the string given in the constructor.');
    }

    /**
     * @test
     */
    public function renderReturnsProperFloatGivenInConstructor()
    {
        $string = '1.1';
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\NumericNode($string);
        $this->assertEquals($node->evaluate($this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContext')), 1.1, 'The rendered value of a numeric node does not match the string given in the constructor.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Parser\Exception
     */
    public function constructorThrowsExceptionIfNoNumericGiven()
    {
        new \TYPO3\Fluid\Core\Parser\SyntaxTree\NumericNode('foo');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Parser\Exception
     */
    public function addChildNodeThrowsException()
    {
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\NumericNode('1');
        $node->addChildNode(clone $node);
    }
}
