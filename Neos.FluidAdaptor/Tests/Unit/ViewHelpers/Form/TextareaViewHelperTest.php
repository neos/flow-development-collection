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

use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Textarea" Form view helper
 */
class TextareaViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\TextareaViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\TextareaViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('textarea');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsNameAttributeAndContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('Current value');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderEscapesTextareaContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects(self::once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects(self::once())->method('setContent')->with('some &lt;tag&gt; &amp; &quot;quotes&quot;');
        $mockTagBuilder->expects(self::once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'some <tag> & "quotes"'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new \Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
