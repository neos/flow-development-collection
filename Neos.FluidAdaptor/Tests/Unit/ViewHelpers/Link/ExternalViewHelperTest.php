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

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for \Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper
 */
class ExternalViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Link\EmailViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Link\ExternalViewHelper::class, ['renderChildren']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndAttributesAndContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'http://www.some-domain.tld']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsHttpPrefixIfSpecifiedUriDoesNotContainScheme()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'http://www.some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'www.some-domain.tld']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsSpecifiedSchemeIfUriDoesNotContainScheme()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'ftp://some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'some-domain.tld', 'defaultScheme' => 'ftp']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderDoesNotAddEmptyScheme()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class, ['setTagName', 'addAttribute', 'setContent']);
        $mockTagBuilder->expects(self::any())->method('setTagName')->with('a');
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('href', 'some-domain.tld');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some content');
        $this->viewHelper->_set('tag', $mockTagBuilder);

        $this->viewHelper->expects(self::any())->method('renderChildren')->will(self::returnValue('some content'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['uri' => 'some-domain.tld', 'defaultScheme' => '']);
        $this->viewHelper->render();
    }
}
