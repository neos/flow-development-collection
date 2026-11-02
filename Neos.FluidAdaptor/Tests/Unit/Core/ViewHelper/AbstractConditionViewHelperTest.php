<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\ViewHelpers\ElseViewHelper;
use TYPO3Fluid\Fluid\ViewHelpers\ThenViewHelper;

require_once(__DIR__ . '/../../ViewHelpers/ViewHelperBaseTestcase.php');

/**
 * Testcase for Condition ViewHelper
 */
final class AbstractConditionViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var AbstractConditionViewHelper|MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(AbstractConditionViewHelper::class, ['renderChildren', 'hasArgument']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderThenChildReturnsAllChildrenIfNoThenViewHelperChildExists()
    {
        $this->viewHelper->method('renderChildren')->willReturn(('foo'));

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('foo', $actualResult);
    }

    #[Test]
    public function renderThenChildReturnsThenViewHelperChildIfConditionIsTrueAndThenViewHelperChildExists()
    {
        $mockThenViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate'], [], '', false);
        $mockThenViewHelperNode->method('getViewHelperClassName')->willReturn(ThenViewHelper::class);
        $mockThenViewHelperNode->method('evaluate')->with($this->renderingContext)->willReturn('ThenViewHelperResults');

        $this->viewHelper->setChildNodes([$mockThenViewHelperNode]);
        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenViewHelperResults', $actualResult);
    }

    #[Test]
    public function renderThenChildReturnsValueOfThenArgumentIfItIsSpecified()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('hasArgument')->with('then')->willReturn((true));
        $this->arguments['then'] = 'ThenArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenArgument', $actualResult);
    }

    #[Test]
    public function renderThenChildReturnsEmptyStringIfChildNodesOnlyContainElseViewHelper()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate'], [], '', false);
        $mockElseViewHelperNode->method('getViewHelperClassName')->willReturn((ElseViewHelper::class));
        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);
        $this->viewHelper->expects($this->never())->method('renderChildren')->willReturn(('Child nodes'));

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('', $actualResult);
    }

    #[Test]
    public function renderElseChildReturnsEmptyStringIfConditionIsFalseAndNoElseViewHelperChildExists()
    {
        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('', $actualResult);
    }

    #[Test]
    public function renderElseChildRendersElseViewHelperChildIfConditionIsFalseAndNoThenViewHelperChildExists()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockElseViewHelperNode->method('getViewHelperClassName')->willReturn((ElseViewHelper::class));
        $mockElseViewHelperNode->method('evaluate')->with($this->renderingContext)->willReturn(('ElseViewHelperResults'));

        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);
        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseViewHelperResults', $actualResult);
    }

    #[Test]
    public function thenArgumentHasPriorityOverChildNodesIfConditionIsTrue()
    {
        $mockThenViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockThenViewHelperNode->expects($this->never())->method('evaluate');

        $this->viewHelper->setChildNodes([$mockThenViewHelperNode]);

        $this->viewHelper->expects($this->atLeastOnce())->method('hasArgument')->with('then')->willReturn((true));
        $this->arguments['then'] = 'ThenArgument';

        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderThenChild');
        self::assertEquals('ThenArgument', $actualResult);
    }

    #[Test]
    public function renderReturnsValueOfElseArgumentIfConditionIsFalse()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('hasArgument')->with('else')->willReturn((true));
        $this->arguments['else'] = 'ElseArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseArgument', $actualResult);
    }

    #[Test]
    public function elseArgumentHasPriorityOverChildNodesIfConditionIsFalse()
    {
        $mockElseViewHelperNode = $this->createMock(ViewHelperNode::class, ['getViewHelperClassName', 'evaluate', 'setRenderingContext'], [], '', false);
        $mockElseViewHelperNode->method('getViewHelperClassName')->willReturn((ElseViewHelper::class));
        $mockElseViewHelperNode->expects($this->never())->method('evaluate');

        $this->viewHelper->setChildNodes([$mockElseViewHelperNode]);

        $this->viewHelper->expects($this->atLeastOnce())->method('hasArgument')->with('else')->willReturn((true));
        $this->arguments['else'] = 'ElseArgument';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->_call('renderElseChild');
        self::assertEquals('ElseArgument', $actualResult);
    }
}
