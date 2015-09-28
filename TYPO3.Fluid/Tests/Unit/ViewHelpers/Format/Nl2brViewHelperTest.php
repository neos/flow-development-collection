<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\Nl2brViewHelper
 */
class Nl2brViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\Nl2brViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\Format\Nl2brViewHelper', array('renderChildren'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperDoesNotModifyTextWithoutLineBreaks()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('<p class="bodytext">Some Text without line breaks</p>'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(10) . 'Line 2'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsWindowsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
    }
}
