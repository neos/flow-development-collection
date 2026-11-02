<?php

declare(strict_types=1);

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
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;
use Neos\FluidAdaptor\ViewHelpers\Form\ButtonViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Button" Form view helper
 */
final class ButtonViewHelperTest extends ViewHelperBaseTestcase
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

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setTagName', 'addAttribute', 'setContent'])->getMock();
        $mockTagBuilder->method('setTagName')->with('button');
        $matcher = self::exactly(3);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('submit', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                // The 'value' argument is unset in this test, so getValueAttribute() returns null.
                $this->assertNull($parameters[1]);
            }
        });
        $mockTagBuilder->expects(self::once())->method('setContent')->with('Button Content');

        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->willReturn('Button Content');

        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }
}
