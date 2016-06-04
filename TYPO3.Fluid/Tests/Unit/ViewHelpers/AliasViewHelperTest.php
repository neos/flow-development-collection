<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for AliasViewHelper
 */
class AliasViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderAddsSingleValueToTemplateVariableContainerAndRemovesItAfterRendering()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\AliasViewHelper();

        $mockViewHelperNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('evaluateChildNodes'), array(), '', false);
        $mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('someAlias');

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($mockViewHelperNode);
        $viewHelper->render(array('someAlias' => 'someValue'));
    }

    /**
     * @test
     */
    public function renderAddsMultipleValuesToTemplateVariableContainerAndRemovesThemAfterRendering()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\AliasViewHelper();

        $mockViewHelperNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('evaluateChildNodes'), array(), '', false);
        $mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('someAlias', 'someValue');
        $this->templateVariableContainer->expects($this->at(1))->method('add')->with('someOtherAlias', 'someOtherValue');
        $this->templateVariableContainer->expects($this->at(2))->method('remove')->with('someAlias');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('someOtherAlias');

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($mockViewHelperNode);
        $viewHelper->render(array('someAlias' => 'someValue', 'someOtherAlias' => 'someOtherValue'));
    }

    /**
     * @test
     */
    public function renderDoesNotTouchTemplateVariableContainerAndReturnsChildNodesIfMapIsEmpty()
    {
        $viewHelper = new \TYPO3\Fluid\ViewHelpers\AliasViewHelper();

        $mockViewHelperNode = $this->createMock('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('evaluateChildNodes'), array(), '', false);
        $mockViewHelperNode->expects($this->once())->method('evaluateChildNodes')->will($this->returnValue('foo'));

        $this->templateVariableContainer->expects($this->never())->method('add');
        $this->templateVariableContainer->expects($this->never())->method('remove');

        $this->injectDependenciesIntoViewHelper($viewHelper);
        $viewHelper->setViewHelperNode($mockViewHelperNode);

        $this->assertEquals('foo', $viewHelper->render(array()));
    }
}
