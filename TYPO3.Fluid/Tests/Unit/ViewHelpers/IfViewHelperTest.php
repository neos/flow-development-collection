<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for IfViewHelper
 */
class IfViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\IfViewHelper
     */
    protected $viewHelper;

    /**
     * @var \TYPO3\Fluid\Core\ViewHelper\Arguments
     */
    protected $mockArguments;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\IfViewHelper', array('renderThenChild', 'renderElseChild'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfConditionIsTrue()
    {
        $this->viewHelper->expects($this->at(0))->method('renderThenChild')->will($this->returnValue('foo'));

        $actualResult = $this->viewHelper->render(true);
        $this->assertEquals('foo', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfConditionIsFalse()
    {
        $this->viewHelper->expects($this->at(0))->method('renderElseChild')->will($this->returnValue('foo'));

        $actualResult = $this->viewHelper->render(false);
        $this->assertEquals('foo', $actualResult);
    }
}
