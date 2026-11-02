<?php

declare(strict_types=1);

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
use Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper
 */
final class Nl2brViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\Nl2brViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(Nl2brViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function viewHelperDoesNotModifyTextWithoutLineBreaks()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn(('<p class="bodytext">Some Text without line breaks</p>'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
    }

    #[Test]
    public function viewHelperConvertsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn(('Line 1' . chr(10) . 'Line 2'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
    }

    #[Test]
    public function viewHelperConvertsWindowsLineBreaksToBRTags()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->willReturn(('Line 1' . chr(13) . chr(10) . 'Line 2'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
    }
}
