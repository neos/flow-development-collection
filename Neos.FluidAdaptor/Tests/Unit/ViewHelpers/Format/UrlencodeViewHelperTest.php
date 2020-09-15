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

use Neos\Flow\Http\Uri;
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

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(UrlencodeViewHelper::class)->setMethods(['renderChildren', 'registerRenderMethodArguments'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
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
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => 'Source']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderUsesChildnodesAsSourceIfSpecified()
    {
        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Source'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Source', $actualResult);
    }

    /**
     * @test
     */
    public function renderDoesNotModifyValueIfItDoesNotContainSpecialCharacters()
    {
        $source = 'StringWithoutSpecialCharacters';
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['value' => $source]);
        $actualResult = $this->viewHelper->render();
        $this->assertSame($source, $actualResult);
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
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function renderThrowsExceptionIfItIsNoStringAndHasNoToStringMethod()
    {
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
        $this->assertEquals(urlencode('http://typo3.com/foo&bar=1'), $actualResult);
    }
}
