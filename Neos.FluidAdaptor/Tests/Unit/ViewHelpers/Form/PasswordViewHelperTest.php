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
use Neos\FluidAdaptor\ViewHelpers\Form\PasswordViewHelper;
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

require_once(__DIR__ . '/Fixtures/EmptySyntaxTreeNode.php');
require_once(__DIR__ . '/Fixtures/Fixture_UserDomainClass.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the "Password" Form view helper
 */
final class PasswordViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\PasswordViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(PasswordViewHelper::class, ['setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration']);
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    #[Test]
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->createMock(TagBuilder::class);
        $mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['setContent', 'render', 'addAttribute'])->getMock();
        $matcher = self::exactly(3);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('password', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('NameOfTextbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('Current value', $parameters[1]);
            }
        });
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextbox');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextbox',
            'value' => 'Current value'
        ];
        $this->viewHelper->setArguments($arguments);

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());
        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    #[Test]
    public function renderCorrectlySetsRequiredAttribute()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->onlyMethods(['addAttribute', 'setContent', 'render'])->disableOriginalConstructor()->getMock();
        $matcher = self::exactly(3);
        $mockTagBuilder->expects($matcher)->method('addAttribute')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                $this->assertSame('type', $parameters[0]);
                $this->assertSame('password', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                $this->assertSame('name', $parameters[0]);
                $this->assertSame('NameOfTextbox', $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 3) {
                $this->assertSame('value', $parameters[0]);
                $this->assertSame('Current value', $parameters[1]);
            }
        });
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextbox');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = [
            'name' => 'NameOfTextbox',
            'value' => 'Current value'
        ];

        $this->viewHelper->setViewHelperNode(new EmptySyntaxTreeNode());

        $this->viewHelper = $this->prepareArguments($this->viewHelper, $arguments);

        $this->viewHelper->render();
    }

    #[Test]
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
