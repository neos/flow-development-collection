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
use Neos\FluidAdaptor\ViewHelpers\Fixtures\EmptySyntaxTreeNode;
use Neos\FluidAdaptor\ViewHelpers\Form\TextareaViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Textarea" Form view helper
 */
final class TextareaViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\TextareaViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(TextareaViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    #[Test]
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('textarea');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsNameAttributeAndContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('Current value');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }

    #[Test]
    public function renderEscapesTextareaContent()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects($this->once())->method('addAttribute')->with('name', 'NameOfTextarea');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextarea');
        $mockTagBuilder->expects($this->once())->method('setContent')->with('some &lt;tag&gt; &amp; &quot;quotes&quot;');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextarea',
            'value' => 'some <tag> & "quotes"'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
