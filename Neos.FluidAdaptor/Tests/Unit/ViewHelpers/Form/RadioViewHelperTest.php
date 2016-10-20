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

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\RadioViewHelper::class, array('setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();

        $this->mockTagBuilder = $this->getMockBuilder(\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder::class)->setMethods(array('setTagName', 'addAttribute'))->getMock();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $this->mockTagBuilder->expects($this->any())->method('setTagName')->with('input');
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
    }

    /**
     * @test
     */
    public function renderIgnoresBoundPropertyIfCheckedIsSet()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue('propertyValue'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
        $this->viewHelper->render(false);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderDoesNotAppendSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(array()));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeString()
    {
        $this->mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'radio');
        $this->mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $this->mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', 'bar');
        $this->mockTagBuilder->expects($this->at(5))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($this->mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCallsSetErrorClassAttribute()
    {
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
