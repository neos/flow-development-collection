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

require_once(__DIR__ . '/../Fixtures/UserWithoutToString.php');
require_once(__DIR__ . '/../Fixtures/UserWithToString.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithoutToString;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithToString;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesDecodeViewHelper
 */
class HtmlentitiesDecodeViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesDecodeViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\HtmlentitiesDecodeViewHelper::class)->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
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
    public function renderUsesValueAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render('Some string');
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Some string'));
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Some string', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'This is a sample text without special characters. <> &©"\'';
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & " \' < > *';
        $actualResult = $this->viewHelper->render($source);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &amp; &quot; \' &lt; &gt; *';
        $expectedResult = 'Some special characters: & &quot; \' < > *';
        $actualResult = $this->viewHelper->render($source, true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &amp; &quot; \' &lt; &gt; *');
        $expectedResult = 'Some special characters: & " \' < > *';
        $actualResult = $this->viewHelper->render($source, false, 'ISO-8859-1');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsUnmodifiedSourceIfItIsANumber()
    {
        $source = 123.45;
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesObjectsToStrings()
    {
        $user = new UserWithToString('Xaver &lt;b&gt;Cross-Site&lt;/b&gt;');
        $expectedResult = 'Xaver <b>Cross-Site</b>';
        $actualResult = $this->viewHelper->render($user);
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifySourceIfItIsAnObjectThatCantBeConvertedToAString()
    {
        $user = new UserWithoutToString('Xaver <b>Cross-Site</b>');
        $actualResult = $this->viewHelper->render($user);
        $this->assertSame($user, $actualResult);
    }
}
