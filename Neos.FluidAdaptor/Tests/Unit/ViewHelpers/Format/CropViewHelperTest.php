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
use Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper
 */
final class CropViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Format\CropViewHelper|MockObject
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(CropViewHelper::class)->onlyMethods(['renderChildren'])->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function viewHelperDoesNotCropTextIfMaxCharactersIsLargerThanNumberOfCharacters()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'some text');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 50]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some text', $actualResult);
    }

    #[Test]
    public function viewHelperAppendsEllipsisToTruncatedText()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'some text');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 5]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some ...', $actualResult);
    }

    #[Test]
    public function viewHelperAppendsCustomSuffix()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'some text');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 3, 'append' => '[custom suffix]']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('som[custom suffix]', $actualResult);
    }

    #[Test]
    public function viewHelperAppendsSuffixEvenIfResultingTextIsLongerThanMaxCharacters()
    {
        $this->simulateViewHelperChildNodeContent($this->viewHelper, 'some text');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8]);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some tex...', $actualResult);
    }

    #[Test]
    public function viewHelperUsesProvidedValueInsteadOfRenderingChildren()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8, 'append' => '...', 'value' => 'some text']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('some tex...', $actualResult);
    }

    #[Test]
    public function viewHelperDoesNotFallbackToRenderChildNodesIfEmptyValueArgumentIsProvided()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 8, 'append' => '...', 'value' => '']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('', $actualResult);
    }

    #[Test]
    public function viewHelperHandlesMultiByteValuesCorrectly()
    {
        $this->viewHelper->expects($this->never())->method('renderChildren');
        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['maxCharacters' => 3, 'append' => '...', 'value' => 'Äßütest']);
        $actualResult = $this->viewHelper->render();
        self::assertEquals('Äßü...', $actualResult);
    }
}
