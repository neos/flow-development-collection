<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\Core\ViewHelper\AbstractConditionViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\ViewHelpers\ElseViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\ThenViewHelper;

require_once(__DIR__ . '/../../ViewHelpers/ViewHelperBaseTestcase.php');

/**
 * Testcase for Condition ViewHelper
 */
class AbstractConditionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var AbstractConditionViewHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(AbstractConditionViewHelper::class, ['getRenderingContext', 'renderChildren', 'hasArgument']);
        $this->viewHelper->expects(self::any())->method('getRenderingContext')->will(self::returnValue($this->renderingContext));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsAllChildrenIfNoThenViewHelperChildExists()
    {
        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('foo'));

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists()
    {
        $mockThenViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate'], [], '', false);
        $mockThenViewHelperNode->method('getViewHelperClassName')->willReturn(ThenViewHelper::class);
        $mockThenViewHelperNode->method('evaluate')->with($this->renderingContext)->willReturn('ThenViewHelperResults');

        $this->viewHelper->setChildNodes([$mockThenViewHelperNode]);
        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenViewHelperResults', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsValueOfThenArgumentIfItIsSpecified()
    {
        $this->viewHelper->expects(self::atLeastOnce())->method('hasArgument')->with('then')->will(self::returnValue(true));
        $this->arguments['then'] = 'ThenArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenArgument', $actualResult);
    }

    /**
     * @test
     */
    public function renderThenChildReturnsEmptyStringIfChildNodesOnlyContainElseViewHelper()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate'], [], '', false);
        $mockElseViewHelperNode->expects(self::any())->method('getViewHelperClassName')->will(self::returnValue(ElseViewHelper::class));
        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);
        $this->viewHelper->expects(self::never())->method('renderChildren')->will(self::returnValue('Child nodes'));

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function renderElseChildReturnsEmptyStringIfConditionIsFalseAndNoElseViewHelperChildExists()
    {
        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function renderElseChildRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockElseViewHelperNode->expects(self::any())->method('getViewHelperClassName')->will(self::returnValue(ElseViewHelper::class));
        $mockElseViewHelperNode->expects(self::any())->method('evaluate')->with($this->renderingContext)->will(self::returnValue('ElseViewHelperResults'));

        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);
        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseViewHelperResults', $actualResult);
    }

    /**
     * @test
     */
    public function thenArgumentHasPriorityOverChildNodesIfConditionIsTrue()
    {
        $mockThenViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockThenViewHelperNode->expects(self::never())->method('evaluate');

        $this->viewHelper->setChildNodes([$mockThenViewHelperNode]);

        $this->viewHelper->expects(self::atLeastOnce())->method('hasArgument')->with('then')->will(self::returnValue(true));
        $this->arguments['then'] = 'ThenArgument';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenArgument', $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsValueOfElseArgumentIfConditionIsFalse()
    {
        $this->viewHelper->expects(self::atLeastOnce())->method('hasArgument')->with('else')->will(self::returnValue(true));
        $this->arguments['else'] = 'ElseArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseArgument', $actualResult);
    }

    /**
     * @test
     */
    public function elseArgumentHasPriorityOverChildNodesIfConditionIsFalse()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockElseViewHelperNode->expects(self::any())->method('getViewHelperClassName')->will(self::returnValue(ElseViewHelper::class));
        $mockElseViewHelperNode->expects(self::never())->method('evaluate');

        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);

        $this->viewHelper->expects(self::atLeastOnce())->method('hasArgument')->with('else')->will(self::returnValue(true));
        $this->arguments['else'] = 'ElseArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseArgument', $actualResult);
    }
}
