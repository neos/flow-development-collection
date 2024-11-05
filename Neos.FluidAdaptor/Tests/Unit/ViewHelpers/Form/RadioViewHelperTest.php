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

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Radio" Form view helper
 */
class RadioViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\RadioViewHelper
     */
    protected $viewHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
     */
    protected $mockTagBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\RadioViewHelper::class, ['setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration']);
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->mockTagBuilder = $this->getMockBuilder(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class)->onlyMethods(['setTagName', 'addAttribute'])->getMock();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects($this->atLeastOnce())->method('setTagName')->with('input');
        $this->mockTagBuilder->expects($this->exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->mockTagBuilder->expects($this->exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderIgnoresBoundPropertyIfCheckedIsSet()
    {
        $this->mockTagBuilder->expects($this->exactly(7))->method('addAttribute')->withConsecutive(
            // first invocation below
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', ''],
            // second invocation below
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->willReturn(('propertyValue'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => false]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $this->mockTagBuilder->expects($this->exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->willReturn((true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects($this->exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->willReturn(([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);


        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString()
    {
        $this->mockTagBuilder->expects($this->exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects($this->any())->method('getName')->willReturn(('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->willReturn(('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->willReturn((true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->willReturn(('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }
}
