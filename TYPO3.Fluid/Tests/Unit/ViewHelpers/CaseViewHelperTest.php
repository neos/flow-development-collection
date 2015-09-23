<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CaseViewHelper
 */
class CaseViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\CaseViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\CaseViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfSwitchExpressionIsNotSetInViewHelperVariableContainer()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(false));
        $this->viewHelper->render('foo');
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));

        $renderedChildNodes = 'ChildNodes';
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

        $this->assertSame($renderedChildNodes, $this->viewHelper->render('someValue'));
    }

    /**
     * @test
     */
    public function renderSetsBreakStateInViewHelperVariableContainerIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));

        $this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break', true);

        $this->viewHelper->render('someValue');
    }

    /**
     * @test
     */
    public function renderWeaklyComparesSpecifiedValueWithSwitchExpression()
    {
        $numericValue = 123;
        $stringValue = '123';

        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue($numericValue));

        $this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break', true);

        $this->viewHelper->render($stringValue);
    }


    /**
     * @test
     */
    public function renderReturnsAnEmptyStringIfTheSpecifiedValueIsNotEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('exists')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue(true));
        $this->viewHelperVariableContainer->expects($this->atLeastOnce())->method('get')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')->will($this->returnValue('someValue'));
        $this->assertSame('', $this->viewHelper->render('someOtherValue'));
    }
}
