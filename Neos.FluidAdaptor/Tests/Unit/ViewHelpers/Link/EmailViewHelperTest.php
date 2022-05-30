<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Link;

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

/**
 */
class EmailViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class);
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['email' => 'some@email.tld']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsTagContentToEmailIfRenderChildrenReturnNull()
    {
        $mockTagBuilder = $this->createMock(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class);
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some@email.tld');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue(null));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['email' => 'some@email.tld']);
        $this->viewHelper->render();
    }
}
