<?php
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

use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Testcase for TagBasedViewHelper
 */
class AbstractTagBasedViewHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AbstractTagBasedViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
    public function initializeResetsUnderlyingTagBuilder()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['reset'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::once())->method('reset');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function oneTagAttributeIsRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = ['foo' => 'bar'];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function additionalTagAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = ['additionalAttributes' => ['foo' => 'bar']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function dataAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::exactly(2))->method('addAttribute')->withConsecutive(['data-foo', 'bar'], ['data-baz', 'foos']);
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = ['data' => ['foo' => 'bar', 'baz' => 'foos']];
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function standardTagAttributesAreRegistered()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(['addAttribute'])->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects(self::exactly(8))->method('addAttribute')->withConsecutive(
            ['class', 'classAttribute'],
            ['dir', 'dirAttribute'],
            ['id', 'idAttribute'],
            ['lang', 'langAttribute'],
            ['style', 'styleAttribute'],
            ['title', 'titleAttribute'],
            ['accesskey', 'accesskeyAttribute'],
            ['tabindex', 'tabindexAttribute']
        );
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
