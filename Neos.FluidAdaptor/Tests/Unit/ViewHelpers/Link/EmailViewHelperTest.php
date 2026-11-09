<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Link;

use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

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
final class EmailViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * var \Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(EmailViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->method('setTagName')->with('a');
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('href', 'mailto:some@email.tld');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some content');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->method('renderChildren')->willReturn(('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['email' => 'some@email.tld']);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderSetsTagContentToEmailIfRenderChildrenReturnNull()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some@email.tld');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->method('renderChildren')->willReturn((null));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['email' => 'some@email.tld']);
        $this->viewHelper->render();
    }
}
