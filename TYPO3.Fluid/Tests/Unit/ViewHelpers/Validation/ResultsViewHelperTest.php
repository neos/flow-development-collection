<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers\Validation;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

include_once(__DIR__ . '/../Fixtures/ConstraintSyntaxTreeNode.php');
require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the Validation Results view helper
 *
 */
class ResultsViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\Validation\ResultsViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(\TYPO3\Fluid\ViewHelpers\Validation\ResultsViewHelper::class)
            ->setMethods(array('renderChildren'))
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderOutputsChildNodesByDefault()
    {
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue(null));
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('child nodes'));

        $this->assertSame('child nodes', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsToTemplateVariableContainer()
    {
        $mockValidationResults = $this->getMockBuilder(\TYPO3\Flow\Error\Result::class)->getMock();
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('validationResults', $mockValidationResults);
        $this->viewHelper->expects($this->once())->method('renderChildren');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('validationResults');

        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsToTemplateVariableContainerWithCustomVariableNameIfSpecified()
    {
        $mockValidationResults = $this->getMockBuilder(\TYPO3\Flow\Error\Result::class)->getMock();
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('customName', $mockValidationResults);
        $this->viewHelper->expects($this->once())->method('renderChildren');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('customName');

        $this->viewHelper->render('', 'customName');
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsForOnePropertyIfForArgumentIsNotEmpty()
    {
        $mockPropertyValidationResults = $this->getMockBuilder(\TYPO3\Flow\Error\Result::class)->getMock();
        $mockValidationResults = $this->getMockBuilder(\TYPO3\Flow\Error\Result::class)->getMock();
        $mockValidationResults->expects($this->once())->method('forProperty')->with('somePropertyName')->will($this->returnValue($mockPropertyValidationResults));
        $this->request->expects($this->atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($mockValidationResults));
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('validationResults', $mockPropertyValidationResults);
        $this->viewHelper->expects($this->once())->method('renderChildren');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('validationResults');

        $this->viewHelper->render('somePropertyName');
    }
}
