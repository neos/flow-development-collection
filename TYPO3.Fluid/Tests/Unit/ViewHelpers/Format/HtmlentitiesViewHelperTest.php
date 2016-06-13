<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
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

use TYPO3\Fluid\ViewHelpers\Fixtures\UserWithoutToString;
use TYPO3\Fluid\ViewHelpers\Fixtures\UserWithToString;
use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper
 */
class HtmlentitiesViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\Format\HtmlentitiesViewHelper')->setMethods(array('renderChildren'))->getMock();
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
        $source = 'This is a sample text without special characters.';
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($source, $actualResult);
    }

    /**
     * @test
     */
    public function renderDecodesSimpleString()
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $actualResult = $this->viewHelper->render($source);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsKeepQuoteArgument()
    {
        $source = 'Some special characters: &©"\'';
        $expectedResult = 'Some special characters: &amp;&copy;"\'';
        $actualResult = $this->viewHelper->render($source, true);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRespectsEncodingArgument()
    {
        $source = utf8_decode('Some special characters: &©"\'');
        $expectedResult = 'Some special characters: &amp;&copy;&quot;\'';
        $actualResult = $this->viewHelper->render($source, false, 'ISO-8859-1');
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderConvertsAlreadyConvertedEntitiesByDefault()
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &amp;quot;encoded&amp;quot;';
        $actualResult = $this->viewHelper->render($source);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotConvertAlreadyConvertedEntitiesIfDoubleQuoteIsFalse()
    {
        $source = 'already &quot;encoded&quot;';
        $expectedResult = 'already &quot;encoded&quot;';
        $actualResult = $this->viewHelper->render($source, false, 'UTF-8', false);
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
    public function renderConvertsObjectsToStrings()
    {
        $user = new UserWithToString('Xaver <b>Cross-Site</b>');
        $expectedResult = 'Xaver &lt;b&gt;Cross-Site&lt;/b&gt;';
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
