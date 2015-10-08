<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
        $this->renderingContext = $this->getMock(\TYPO3\Fluid\Core\Rendering\RenderingContext::class, array(), array(), '', false);

        $this->abstractNode = $this->getMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode::class, array('evaluate'));

        $this->childNode = $this->getMock(\TYPO3\Fluid\Core\Parser\SyntaxTree\AbstractNode::class);
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
