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
use Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper
 */
final class PaddingViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\PaddingViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(PaddingViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function stringsArePaddedWithBlanksByDefault()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'foo');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 10]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('foo       ', $actualResult);
    }

    #[Test]
    public function paddingStringCanBeSpecified()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'foo');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 10, 'padString' => '-=']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('foo-=-=-=-', $actualResult);
    }

    #[Test]
    public function stringIsNotTruncatedIfPadLengthIsBelowStringLength()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'some long string');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 5]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some long string', $actualResult);
    }

    #[Test]
    public function integersArePaddedCorrectly()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 123);
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['padLength' => 5, 'padString' => '0']);
        $actualResult = $this->viewHelper->render(5, '0');
        self::assertEquals('12300', $actualResult);
    }
}
