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
 * Test for Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper
 */
class PaddingViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function stringsArePaddedWithBlanksByDefault()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('foo'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 10]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('foo       ', $actualResult);
    }

    /**
     * @test
     */
    public function paddingStringCanBeSpecified()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('foo'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 10, 'padString' => '-=']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('foo-=-=-=-', $actualResult);
    }

    /**
     * @test
     */
    public function stringIsNotTruncatedIfPadLengthIsBelowStringLength()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('some long string'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 5]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some long string', $actualResult);
    }

    /**
     * @test
     */
    public function integersArePaddedCorrectly()
    {
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue(123));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 5, 'padString' => '0']);
        $actualResult = $this->viewHelper->render(5, '0');
        self::assertEquals('12300', $actualResult);
    }
}
