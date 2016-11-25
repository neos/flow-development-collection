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
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Testcase for TagBasedViewHelper
 */
class AbstractTagBasedViewHelperTest extends \Neos\Flow\Tests\UnitTestCase
{
    public function setUp()
    {
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper::class, array('dummy'), array(), '', false);
    }

    /**
     * @test
     */
    public function initializeResetsUnderlyingTagBuilder()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('reset'))->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('reset');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function oneTagAttributeIsRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('addAttribute'))->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = array('foo' => 'bar');
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function additionalTagAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('addAttribute'))->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('foo', 'bar');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->_call('registerTagAttribute', 'foo', 'string', 'Description', false);
        $arguments = array('additionalAttributes' => array('foo' => 'bar'));
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function dataAttributesAreRenderedCorrectly()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('addAttribute'))->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('data-foo', 'bar');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('data-baz', 'foos');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = array('data' => array('foo' => 'bar', 'baz' => 'foos'));
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function standardTagAttributesAreRegistered()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('addAttribute'))->disableOriginalConstructor()->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('class', 'classAttribute');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('dir', 'dirAttribute');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('id', 'idAttribute');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('lang', 'langAttribute');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('style', 'styleAttribute');
        $mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('title', 'titleAttribute');
        $mockTagBuilder->expects($this->at(6))->method('addAttribute')->with('accesskey', 'accesskeyAttribute');
        $mockTagBuilder->expects($this->at(7))->method('addAttribute')->with('tabindex', 'tabindexAttribute');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = array(
            'class' => 'classAttribute',
            'dir' => 'dirAttribute',
            'id' => 'idAttribute',
            'lang' => 'langAttribute',
            'style' => 'styleAttribute',
            'title' => 'titleAttribute',
            'accesskey' => 'accesskeyAttribute',
            'tabindex' => 'tabindexAttribute'
        );
        $this->viewHelper->_call('registerUniversalTagAttributes');
        $this->viewHelper->setArguments($arguments);
        $this->viewHelper->initializeArguments();
        $this->viewHelper->initialize();
    }
}
