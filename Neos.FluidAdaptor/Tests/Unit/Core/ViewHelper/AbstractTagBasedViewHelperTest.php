<?php

declare(strict_types=1);

namespace Neos\FluidAdaptor\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Testcase for TagBasedViewHelper
 */
final class AbstractTagBasedViewHelperTest extends UnitTestCase
{
    /**
     * @var MockObject|AbstractTagBasedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = $this->getAccessibleMock(AbstractTagBasedViewHelper::class, [], [], '', false);
    }

    #[Test]
    public function initializeResetsUnderlyingTagBuilder()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['reset'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('reset');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
    }

    #[Test]
    public function oneTagAttributeIsRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = ['foo' => 'bar'];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    #[Test]
    public function additionalTagAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = ['additionalAttributes' => ['foo' => 'bar']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    #[Test]
    public function dataAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $matcher = self::exactly(2);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('data-foo', $parameters[0]);
                $this->assertSame('bar', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('data-baz', $parameters[0]);
                $this->assertSame('foos', $parameters[1]);
            }
        });
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = ['data' => ['foo' => 'bar', 'baz' => 'foos']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    #[Test]
    public function standardTagAttributesAreRegistered()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $matcher = self::exactly(8);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('class', $parameters[0]);
                $this->assertSame('classAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('dir', $parameters[0]);
                $this->assertSame('dirAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('id', $parameters[0]);
                $this->assertSame('idAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 4) {
                $this->assertSame('lang', $parameters[0]);
                $this->assertSame('langAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 5) {
                $this->assertSame('style', $parameters[0]);
                $this->assertSame('styleAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 6) {
                $this->assertSame('title', $parameters[0]);
                $this->assertSame('titleAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 7) {
                $this->assertSame('accesskey', $parameters[0]);
                $this->assertSame('accesskeyAttribute', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 8) {
                $this->assertSame('tabindex', $parameters[0]);
                $this->assertSame('tabindexAttribute', $parameters[1]);
            }
        });
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'class' => 'classAttribute',
            'dir' => 'dirAttribute',
            'id' => 'idAttribute',
            'lang' => 'langAttribute',
            'style' => 'styleAttribute',
            'title' => 'titleAttribute',
            'accesskey' => 'accesskeyAttribute',
            'tabindex' => 'tabindexAttribute'
        ];
        $this->viewHelper->_call('registerUniversalTagAttributes');
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
    }
}
