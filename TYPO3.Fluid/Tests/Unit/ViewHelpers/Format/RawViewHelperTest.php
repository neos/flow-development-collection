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

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
 */
class RawViewHelperTest extends UnitTestCase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\RawViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\Format\RawViewHelper')->setMethods(array('renderChildren'))->getMock();
    }

    /**
     * @test
     */
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        $this->assertFalse($this->viewHelper->isEscapingInterceptorEnabled());
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedValueIfSpecified()
    {
        $value = 'input value " & äöüß@';
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render($value);
        $this->assertEquals($value, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedChildNodesIfNoValueIsSpecified()
    {
        $childNodes = 'input value " & äöüß@';
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue($childNodes));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals($childNodes, $actualResult);
    }
}
