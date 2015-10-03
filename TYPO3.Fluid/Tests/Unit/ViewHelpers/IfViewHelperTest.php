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
