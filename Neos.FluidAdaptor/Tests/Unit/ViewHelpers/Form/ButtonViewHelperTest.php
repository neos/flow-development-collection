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

require_once(__DIR__ . '/FormFieldViewHelperBaseTestcase.php');

/**
 * Test for the "Button" Form view helper
 */
class ButtonViewHelperTest extends \Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \Neos\FluidAdaptor\ViewHelpers\Form\ButtonViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Form\ButtonViewHelper::class, array('renderChildren'));
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder(TagBuilder::class)->setMethods(array('setTagName', 'addAttribute', 'setContent'))->getMock();
        $mockTagBuilder->expects($this->any())->method('setTagName')->with('button');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('type', 'submit');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('name', '');
        $mockTagBuilder->expects($this->at(4))->method('addAttribute')->with('value', '');
        $mockTagBuilder->expects($this->at(5))->method('setContent')->with('Button Content');

        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Button Content'));

        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
