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
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\CaseViewHelper')->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionIfSwitchExpressionIsNotSetInViewHelperVariableContainer()
    {
        $this->viewHelper->render('foo');
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\SwitchViewHelper' => array(
                'switchExpression' => 'someValue',
            )
        );

        $renderedChildNodes = 'ChildNodes';
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($renderedChildNodes));

        $this->assertSame($renderedChildNodes, $this->viewHelper->render('someValue'));
    }

    /**
     * @test
     */
    public function renderSetsBreakStateInViewHelperVariableContainerIfTheSpecifiedValueIsEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\SwitchViewHelper' => array(
                'switchExpression' => 'someValue',
            )
        );
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

        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\SwitchViewHelper' => array(
                'switchExpression' => $numericValue,
            )
        );

        $this->viewHelperVariableContainer->expects($this->once())->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'break', true);

        $this->viewHelper->render($stringValue);
    }


    /**
     * @test
     */
    public function renderReturnsAnEmptyStringIfTheSpecifiedValueIsNotEqualToTheSwitchExpression()
    {
        $this->viewHelperVariableContainerData = array(
            'TYPO3\Fluid\ViewHelpers\SwitchViewHelper' => array(
                'switchExpression' => 'someValue',
            )
        );
        $this->assertSame('', $this->viewHelper->render('someOtherValue'));
    }
}
