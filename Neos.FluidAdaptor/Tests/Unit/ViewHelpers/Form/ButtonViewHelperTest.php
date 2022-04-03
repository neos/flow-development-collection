<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\FluidAdaptor\ViewHelpers\Form\ButtonViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Button" Form view helper
 */
class ButtonViewHelperTest extends FormFieldViewHelperBaseTestcase
{
    /**
     * @var ButtonViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(ButtonViewHelper::class, ['renderChildren']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['setTagName', 'addAttribute', 'setContent'])->getMock();
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('button');
        $mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'submit'],
            ['name', ''],
            ['value', '']
        );
        $mockTagBuilder->expects(self::once())->method('setContent')->with('Button Content');

        $this->viewHelper->expects(self::atLeastOnce())->method('renderChildren')->willReturn('Button Content');

        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }
}
