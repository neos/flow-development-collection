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
 * Test for the "Textfield" Form view helper
 */
class TextfieldViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\TextfieldViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\TextfieldViewHelper::class, array('setErrorClassAttribute', 'registerFieldNameForFormTokenGeneration'));
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagName()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('setTagName'))->getMock();
        $mockTagBuilder->expects($this->any())->method('setTagName')->with('input');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTypeNameAndValueAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('setContent', 'render', 'addAttribute'))->getMock();
        $mockTagBuilder->expects($this->at(0))->method('addAttribute')->with('type', 'text');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('name', 'NameOfTextfield');
        $this->viewHelper->expects($this->once())->method('registerFieldNameForFormTokenGeneration')->with('NameOfTextfield');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('value', 'Current value');
        $mockTagBuilder->expects($this->once())->method('render');
        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $arguments = array(
            'name' => 'NameOfTextfield',
            'value' => 'Current value'
        );
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
        $this->viewHelper->expects($this->once())->method('setErrorClassAttribute');
        $this->viewHelper->render();
    }
}
