<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Format;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');
require_once(__DIR__ . '/../Fixtures/UserWithoutToString.php');
require_once(__DIR__ . '/../Fixtures/UserWithToString.php');

use Neos\FluidAdaptor\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithoutToString;
use Neos\FluidAdaptor\ViewHelpers\Fixtures\UserWithToString;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper
 */
class StripTagsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\StripTagsViewHelper::class)->setMethods(array('renderChildren'))->getMock();
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
     * Data Provider for the render tests
     *
     * @return array
     */
    public function stringsTestDataProvider()
    {
        return array(
            array('This is a sample text without special characters.', 'This is a sample text without special characters.'),
            array('This is a sample text <b>with <i>some</i> tags</b>.', 'This is a sample text with some tags.'),
            array('This text contains some &quot;&Uuml;mlaut&quot;.', 'This text contains some &quot;&Uuml;mlaut&quot;.')
        );
    }

    /**
     * @test
     * @dataProvider stringsTestDataProvider
     */
    public function renderCorrectlyConvertsIntoPlaintext($source, $expectedResult)
    {
        $actualResult = $this->viewHelper->render($source);
        $this->assertSame($expectedResult, $actualResult);
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
        $expectedResult = 'Xaver Cross-Site';
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
