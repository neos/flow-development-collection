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
 * Testcase for SwitchViewHelper
 */
class SwitchViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\SwitchViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\SwitchViewHelper')->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderSetsSwitchExpressionInViewHelperVariableContainer()
    {
        $switchExpression = new \stdClass();
        $this->viewHelperVariableContainer->expects($this->at(2))->method('addOrUpdate')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression', $switchExpression);
        $this->viewHelper->render($switchExpression);
    }

    /**
     * @test
     */
    public function renderRemovesSwitchExpressionFromViewHelperVariableContainerAfterInvocation()
    {
        $this->viewHelperVariableContainer->expects($this->at(4))->method('remove')->with('TYPO3\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');
        $this->viewHelper->render('switchExpression');
    }
}
