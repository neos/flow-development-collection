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

use GuzzleHttp\Psr7\Uri;
use Neos\FluidAdaptor\ViewHelpers\Format\UrlencodeViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\UrlencodeViewHelper
 */
class UrlencodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var UrlencodeViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(UrlencodeViewHelper::class)->setMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperDeactivatesEscapingInterceptor()
    {
        self::assertFalse($this->viewHelper->isEscapingInterceptorEnabled());
    }

    /**
     * @test
     */
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects(self::never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'Source']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects(self::atLeastOnce())->method('renderChildren')->will(self::returnValue('Source'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'StringWithoutSpecialCharacters';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderEncodesString()
    {
        $source = 'Foo @+%/ "';
        $expectedResult = 'Foo%20%40%2B%25%2F%20%22';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderThrowsExceptionIfItIsNoStringAndHasNoToStringMethod()
    {
        $this->expectException(\InvalidArgumentException::class);
        $source = new \stdClass();
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderRendersObjectWithToStringMethod()
    {
        $source = new Uri('http://typo3.com/foo&bar=1');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals(urlencode('http://typo3.com/foo&bar=1'), $actualResult);
    }
}
