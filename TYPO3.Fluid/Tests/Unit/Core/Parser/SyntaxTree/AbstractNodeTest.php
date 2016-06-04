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
 * An AbstractNode Test
 */
class AbstractNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    protected $renderingContext;

    protected $abstractNode;

    protected $childNode;

    public function setUp()
    {
        $this->renderingContext = $this->getMockBuilder('TYPO3\Fluid\Core\Rendering\RenderingContext')->disableOriginalConstructor()->getMock();

        $this->abstractNode = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode')->setMethods(array('evaluate'))->getMock();

        $this->childNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode');
        $this->abstractNode->addChildNode($this->childNode);
    }

    /**
     * @test
     */
    public function evaluateChildNodesPassesRenderingContextToChildNodes()
    {
        $this->childNode->expects($this->once())->method('evaluate')->with($this->renderingContext);
        $this->abstractNode->evaluateChildNodes($this->renderingContext);
    }

    /**
     * @test
     */
    public function childNodeCanBeReadOutAgain()
    {
        $this->assertSame($this->abstractNode->getChildNodes(), array($this->childNode));
    }
}
