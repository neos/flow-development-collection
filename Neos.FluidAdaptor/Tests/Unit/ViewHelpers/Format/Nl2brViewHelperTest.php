<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper
 */
class Nl2brViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotModifyTextWithoutLineBreaks()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('<p class="bodytext">Some Text without line breaks</p>'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsLineBreaksToBRTags()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Line 1' . chr(10) . 'Line 2'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperConvertsWindowsLineBreaksToBRTags()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
    }
}
