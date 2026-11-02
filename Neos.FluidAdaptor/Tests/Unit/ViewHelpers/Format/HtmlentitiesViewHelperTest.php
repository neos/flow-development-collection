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

require_once(__DIR__ . '/../Fixtures/UserWithoutToString.php');
require_once(__DIR__ . '/../Fixtures/UserWithToString.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithoutToString;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithToString;
use Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesViewHelper;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesViewHelper
 */
final class HtmlentitiesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(HtmlentitiesViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
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
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'Some string']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Some string', $actualResult);
    }

    #[Test]
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'Some string');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Some string', $actualResult);
    }

    #[Test]
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters.';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($source, $actualResult);
    }

    #[Test]
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;"\'';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source, 'keepQuotes' => true]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderRespectsEncodingArgument()
    {
        $source = mb_convert_encoding('Some special characters: &©"\'', 'ISO-8859-1', 'UTF-8');
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source, 'keepQuotes' => false, 'encoding' => 'ISO-8859-1']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderConvertsAlreadyConvertedEntitiesByDefault()
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &amp;quot;encoded&amp;quot;';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse()
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &quot;encoded&quot;';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source, 'keepQuotes' => false, 'encoding' => 'UTF-8', 'doubleEncode' => false]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function renderReturnsUnmodifiedSourceIfItIsANumber()
    {
        $source = 123.45;
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($source, $actualResult);
    }

    #[Test]
    public function renderConvertsObjectsToStrings()
    {
        $user = new UserWithToString('Xaver <b>Cross-Site</b>');
        $expectedResult = 'Xaver &lt;b&gt;Cross-Site&lt;/b&gt;';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $user]);
        $actualResult = $this->viewHelper->render();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function renderDoesNotModifySourceIfItIsAnObjectThatCantBeConvertedToAString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $user = new UserWithoutToString('Xaver <b>Cross-Site</b>');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $user]);
        $this->viewHelper->render();
    }
}
