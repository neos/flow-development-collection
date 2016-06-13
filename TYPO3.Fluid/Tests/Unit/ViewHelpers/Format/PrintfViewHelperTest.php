<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\PrintfViewHelper
 */
class PrintfViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\PrintfViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\Format\PrintfViewHelper')->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperCanUseArrayAsArgument()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%04d-%02d-%02d'));
        $actualResult = $this->viewHelper->render(array('year' => 2009, 'month' => 4, 'day' => 5));
        $this->assertEquals('2009-04-05', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperCanSwapMultipleArguments()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%2$s %1$d %3$s %2$s'));
        $actualResult = $this->viewHelper->render(array(123, 'foo', 'bar'));
        $this->assertEquals('foo 123 bar foo', $actualResult);
    }
}
