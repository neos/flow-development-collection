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
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper
 */
class CropViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper::class)->setMethods(['renderChildren', 'registerRenderMethodArguments'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotCropTextIfMaxCharactersIsLargerThanNumberOfCharacters()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 50]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('some text', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsEllipsisToTruncatedText()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 5]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('some ...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsCustomSuffix()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 3, 'append' => '[custom suffix]']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('som[custom suffix]', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsSuffixEvenIfResultingTextIsLongerThanMaxCharacters()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8]);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('some tex...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesProvidedValueInsteadOfRenderingChildren()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8, 'append' => '...', 'value' => 'some text']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('some tex...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotFallbackToRenderChildNodesIfEmptyValueArgumentIsProvided()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8, 'append' => '...', 'value' => '']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesMultiByteValuesCorrectly()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 3, 'append' => '...', 'value' => 'Äßütest']);
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('Äßü...', $actualResult);
    }
}
