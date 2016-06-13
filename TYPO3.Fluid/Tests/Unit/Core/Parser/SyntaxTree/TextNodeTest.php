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
 * Testcase for TextNode
 */
class TextNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function renderReturnsSameStringAsGivenInConstructor()
    {
        $string = 'I can work quite effectively in a train!';
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode($string);
        $this->assertEquals($node->evaluate($this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContext')), $string, 'The rendered string of a text node is not the same as the string given in the constructor.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\Parser\Exception
     */
    public function constructorThrowsExceptionIfNoStringGiven()
    {
        new \TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode(123);
    }
}
