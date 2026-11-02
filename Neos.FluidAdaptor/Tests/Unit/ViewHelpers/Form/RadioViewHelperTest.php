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
use Neos\FluidAdaptor\ViewHelpers\Form\RadioViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Radio" Form view helper
 */
final class RadioViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\RadioViewHelper
     */
    protected $viewHelper;

    /**
     * @var MockObject|\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $mockTagBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(RadioViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setTagName', 'addAttribute'])->getMock();
    }

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');
        $matcher = self::exactly(3);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
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
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderIgnoresBoundPropertyIfCheckedIsSet()
    {
        $matcher = self::exactly(7);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 5) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 6) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 7) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
        });

        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn(('propertyValue'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => false]);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn((true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $matcher = self::exactly(3);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
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
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn(([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);


        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString()
    {
        $matcher = self::exactly(4);
        $this->mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('radio', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('foo', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('checked', $parameters[0]);
                $this->assertSame('', $parameters[1]);
            }
        });

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->method('getName')->willReturn(('foo'));
        $this->viewHelper->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->method('getPropertyValue')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }
}
