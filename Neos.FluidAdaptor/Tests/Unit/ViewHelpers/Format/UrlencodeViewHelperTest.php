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

use GuzzleHttp\Psr7\Uri;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Format\UrlencodeViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\UrlencodeViewHelper
 */
final class UrlencodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var UrlencodeViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(UrlencodeViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        self::assertFalse($this->viewHelper->isEscapingInterceptorEnabled());
    }

    #[Test]
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'Source']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Source', $actualResult);
    }

    #[Test]
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'Source');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Source', $actualResult);
    }

    #[Test]
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'StringWithoutSpecialCharacters';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($source, $actualResult);
    }

    #[Test]
    public function renderEncodesString()
    {
        $source = 'Foo @+%/ "';
        $expectedResult = 'Foo%20%40%2B%25%2F%20%22';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderThrowsExceptionIfItIsNoStringAndHasNoToStringMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $source = new \stdClass();
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderRendersObjectWithToStringMethod()
    {
        $source = new Uri('http://typo3.com/foo&bar=1');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals(urlencode('http://typo3.com/foo&bar=1'), $actualResult);
    }
}
