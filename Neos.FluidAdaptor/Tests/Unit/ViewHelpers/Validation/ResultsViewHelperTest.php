<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Validation;

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
use Neos\FluidAdaptor\ViewHelpers\Validation\ResultsViewHelper;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 * Test for the Validation Results view helper
 *
 */
class ResultsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var ResultsViewHelper
     */
    protected $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder(ResultsViewHelper::class)
            ->setMethods(['renderChildren'])
            ->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function renderOutputsChildNodesByDefault()
    {
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will(self::returnValue(null));
        $this->viewHelper->expects(self::once())->method('renderChildren')->will(self::returnValue('child nodes'));

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        self::assertSame('child nodes', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsToTemplateVariableContainer()
    {
        $mockValidationResults = $this->getMockBuilder(\Neos\Error\Messages\Result::class)->getMock();
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will(self::returnValue($mockValidationResults));
        $this->templateVariableContainer->expects(self::once())->method('add')->with('validationResults', $mockValidationResults);
        $this->viewHelper->expects(self::once())->method('renderChildren');
        $this->templateVariableContainer->expects(self::once())->method('remove')->with('validationResults');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, []);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsToTemplateVariableContainerWithCustomVariableNameIfSpecified()
    {
        $mockValidationResults = $this->getMockBuilder(\Neos\Error\Messages\Result::class)->getMock();
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will(self::returnValue($mockValidationResults));
        $this->templateVariableContainer->expects(self::once())->method('add')->with('customName', $mockValidationResults);
        $this->viewHelper->expects(self::once())->method('renderChildren');
        $this->templateVariableContainer->expects(self::once())->method('remove')->with('customName');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['for' => '', 'as' => 'customName']);
        $this->viewHelper->render();
    }

    /**
     * @test
     */
    public function renderAddsValidationResultsForOnePropertyIfForArgumentIsNotEmpty()
    {
        $mockPropertyValidationResults = $this->getMockBuilder(\Neos\Error\Messages\Result::class)->getMock();
        $mockValidationResults = $this->getMockBuilder(\Neos\Error\Messages\Result::class)->getMock();
        $mockValidationResults->expects(self::once())->method('forProperty')->with('somePropertyName')->will(self::returnValue($mockPropertyValidationResults));
        $this->request->expects(self::atLeastOnce())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will(self::returnValue($mockValidationResults));
        $this->templateVariableContainer->expects(self::once())->method('add')->with('validationResults', $mockPropertyValidationResults);
        $this->viewHelper->expects(self::once())->method('renderChildren');
        $this->templateVariableContainer->expects(self::once())->method('remove')->with('validationResults');

        $this->viewHelper = $this->prepareArguments($this->viewHelper, ['for' => 'somePropertyName']);
        $this->viewHelper->render();
    }
}
