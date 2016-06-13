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

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

use TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase;

/**
 * Test for \TYPO3\Fluid\ViewHelpers\Format\CropViewHelper
 */
class CropViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Format\CropViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\Format\CropViewHelper')->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperDoesNotCropTextIfMaxCharactersIsLargerThanNumberOfCharacters()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $actualResult = $this->viewHelper->render(50);
        $this->assertEquals('some text', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsEllipsisToTruncatedText()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $actualResult = $this->viewHelper->render(5);
        $this->assertEquals('some ...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsCustomSuffix()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $actualResult = $this->viewHelper->render(3, '[custom suffix]');
        $this->assertEquals('som[custom suffix]', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperAppendsSuffixEvenIfResultingTextIsLongerThanMaxCharacters()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some text'));
        $actualResult = $this->viewHelper->render(8);
        $this->assertEquals('some tex...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperUsesProvidedValueInsteadOfRenderingChildren()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render(8, '...', 'some text');
        $this->assertEquals('some tex...', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperDoesNotFallbackToRenderChildNodesIfEmptyValueArgumentIsProvided()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render(8, '...', '');
        $this->assertEquals('', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperHandlesMultiByteValuesCorrectly()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $actualResult = $this->viewHelper->render(3, '...', 'Äßütest');
        $this->assertEquals('Äßü...', $actualResult);
    }
}
