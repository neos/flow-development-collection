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
use Neos\FluidAdaptor\ViewHelpers\Form\HiddenViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Hidden" Form view helper
 */
final class HiddenViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\HiddenViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(HiddenViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setTagName', 'addAttribute'])->getMock();
        $mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');
        $matcher = self::exactly(3);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('hidden', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->once())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->once())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
