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
        $this->viewHelper = $this->getMock(\TYPO3\Fluid\ViewHelpers\SwitchViewHelper::class, array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderSetsSwitchExpressionInViewHelperVariableContainer()
    {
        $switchExpression = new \stdClass();
        $this->viewHelperVariableContainer->expects($this->at(2))->method('addOrUpdate')->with(\TYPO3\Fluid\ViewHelpers\SwitchViewHelper::class, 'switchExpression', $switchExpression);
        $this->viewHelper->render($switchExpression);
    }

    /**
     * @test
     */
    public function renderRemovesSwitchExpressionFromViewHelperVariableContainerAfterInvocation()
    {
        $this->viewHelperVariableContainer->expects($this->at(4))->method('remove')->with(\TYPO3\Fluid\ViewHelpers\SwitchViewHelper::class, 'switchExpression');
        $this->viewHelper->render('switchExpression');
    }
}
