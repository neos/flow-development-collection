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
        $this->mockTagBuilder = $this->getMockBuilder(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class)->setMethods(['setTagName', 'addAttribute'])->getMock();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects(self::atLeastOnce())->method('setTagName')->with('input');
        $this->mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['checked' => true]);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderIgnoresBoundPropertyIfCheckedIsSet()
    {
        $this->mockTagBuilder->expects(self::exactly(7))->method('addAttribute')->withConsecutive(
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

        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue('propertyValue'));
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
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue(true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects(self::exactly(3))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue([]));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);


        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString()
    {
        $this->mockTagBuilder->expects(self::exactly(4))->method('addAttribute')->withConsecutive(
            ['type', 'radio'],
            ['name', 'foo'],
            ['value', 'bar'],
            ['checked', '']
        );

        $this->viewHelper->expects(self::once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->viewHelper->expects(self::any())->method('getName')->will(self::returnValue('foo'));
        $this->viewHelper->expects(self::any())->method('getValueAttribute')->will(self::returnValue('bar'));
        $this->viewHelper->expects(self::any())->method('isObjectAccessorMode')->will(self::returnValue(true));
        $this->viewHelper->expects(self::any())->method('getPropertyValue')->will(self::returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects(self::once())->method('setErrorClassAttribute');
        $this->viewHelper = $this->prepareArguments($this->viewHelper);
        $this->viewHelper->render();
    }
}
