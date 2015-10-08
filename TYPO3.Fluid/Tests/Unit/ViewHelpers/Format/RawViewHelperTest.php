<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
        $this->viewHelper = $this->getMock(\TYPO3\Fluid\ViewHelpers\Format\RawViewHelper::class, array('renderChildren'));
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
