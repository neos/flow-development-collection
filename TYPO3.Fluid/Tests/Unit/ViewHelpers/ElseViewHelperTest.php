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
 * Testcase for ElseViewHelper
 */
class ElseViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @test
     */
    public function renderRendersChildren()
    {
        $viewHelper = $this->getMock('TYPO3\Fluid\ViewHelpers\ElseViewHelper', array('renderChildren'));

        $viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
        $actualResult = $viewHelper->render();
        $this->assertEquals('foo', $actualResult);
    }
}
