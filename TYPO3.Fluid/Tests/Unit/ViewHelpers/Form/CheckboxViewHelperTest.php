<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Form;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Checkbox" Form view helper
 */
class CheckboxViewHelperTest extends \TYPO3\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Form\CheckboxViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\Fluid\ViewHelpers\Form\CheckboxViewHelper::class, array('setErrorClassAttribute', 'getName', 'getValueAttribute', 'isObjectAccessorMode', 'getPropertyValue', 'registerFieldNameForFormTokenGeneration'));
        $this->arguments['property'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('input');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfSpecified()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
    }

    /**
     * @test
     */
    public function renderIgnoresValueOfBoundPropertyIfCheckedIsSet()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render(true);
        $this->viewHelper->render(false);
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeBoolean()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(true));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAppendsSquareBracketsToNameAttributeIfBoundToAPropertyOfTypeArray()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(array()));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArray()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(array('foo', 'bar', 'baz')));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsCheckedAttributeIfCheckboxIsBoundToAPropertyOfTypeArrayObject()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo[]');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \ArrayObject(array('foo', 'bar', 'baz'))));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderSetsCheckedAttributeIfBoundPropertyIsNotNull()
    {
        $mockTagBuilder = $this->getMock(\TYPO3\Fluid\Core\ViewHelper\TagBuilder::class, array('setTagName', 'addAttribute'));
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'checkbox');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', 'foo');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', 'bar');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('checked', 'checked');

        $this->viewHelper->expects($this->any())->method('getName')->will($this->returnValue('foo'));
        $this->viewHelper->expects($this->any())->method('getValueAttribute')->will($this->returnValue('bar'));
        $this->viewHelper->expects($this->any())->method('isObjectAccessorMode')->will($this->returnValue(true));
        $this->viewHelper->expects($this->any())->method('getPropertyValue')->will($this->returnValue(new \stdClass()));
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

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
