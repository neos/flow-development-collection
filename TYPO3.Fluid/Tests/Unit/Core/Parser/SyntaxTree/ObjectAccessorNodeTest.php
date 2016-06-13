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
 * Testcase for ObjectAccessorNode
 */
class ObjectAccessorNodeTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function evaluateGetsPropertyPathFromVariableContainer()
    {
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode('foo.bar');
        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
        $variableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array(
            'foo' => array(
                'bar' => 'some value'
            )
        ));
        $renderingContext->expects($this->any())->method('getTemplateVariableContainer')->will($this->returnValue($variableContainer));

        $value = $node->evaluate($renderingContext);

        $this->assertEquals('some value', $value);
    }

    /**
     * @test
     */
    public function evaluateCallsObjectAccessOnSubjectWithTemplateObjectAccessInterface()
    {
        $node = new \TYPO3\Fluid\Core\Parser\SyntaxTree\ObjectAccessorNode('foo.bar');
        $renderingContext = $this->createMock('TYPO3\Fluid\Core\Rendering\RenderingContextInterface');
        $templateObjectAcessValue = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\TemplateObjectAccessInterface');
        $variableContainer = new \TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer(array(
            'foo' => array(
                'bar' => $templateObjectAcessValue
            )
        ));
        $renderingContext->expects($this->any())->method('getTemplateVariableContainer')->will($this->returnValue($variableContainer));

        $templateObjectAcessValue->expects($this->once())->method('objectAccess')->will($this->returnValue('special value'));

        $value = $node->evaluate($renderingContext);

        $this->assertEquals('special value', $value);
    }
}
