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
 * Test for the "Button" Form view helper
 */
class ButtonViewHelperTest extends \TYPO3\Fluid\Tests\Unit\ViewHelpers\Form\FormFieldViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Form\ButtonViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock('TYPO3\Fluid\ViewHelpers\Form\ButtonViewHelper', array('renderChildren'));
        $this->arguments['name'] = '';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderCorrectlySetsTagNameAndDefaultAttributes()
    {
        $mockTagBuilder = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\TagBuilder')->setMethods(array('setTagName', 'addAttribute', 'setContent'))->getMock();
        $mockTagBuilder->expects($this->once())->method('setTagName')->with('button');
        $mockTagBuilder->expects($this->at(1))->method('addAttribute')->with('type', 'submit');
        $mockTagBuilder->expects($this->at(2))->method('addAttribute')->with('name', '');
        $mockTagBuilder->expects($this->at(3))->method('addAttribute')->with('value', '');
        $mockTagBuilder->expects($this->at(4))->method('setContent')->with('Button Content');

        $this->viewHelper->expects($this->atLeastOnce())->method('renderChildren')->will($this->returnValue('Button Content'));

        $this->viewHelper->injectTagBuilder($mockTagBuilder);

        $this->viewHelper->initialize();
        $this->viewHelper->render();
    }
}
