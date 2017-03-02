<?php
namespace Neos\FluidAdaptor\Tests\Unit\ViewHelpers\Security;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Error\Messages\Error;
use Neos\Error\Messages\Result;
use Neos\FluidAdaptor\ViewHelpers\Validation\IfHasErrorsViewHelper;
use Neos\FluidAdaptor\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase;

require_once(__DIR__ . '/../ViewHelperBaseTestcase.php');

/**
 */
class IfHasErrorsViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var IfHasErrorsViewHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewHelper;

    /**
     */
    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getAccessibleMock(\Neos\FluidAdaptor\ViewHelpers\Validation\IfHasErrorsViewHelper::class, array('renderThenChild', 'renderElseChild'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
    }

    /**
     * @test
     */
    public function returnsAndRendersThenChildIfResultsHaveErrors()
    {
        $result = new Result;
        $result->addError(new Error('I am an error', 1386163707));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($result));
        $this->viewHelper->expects($this->once())->method('renderThenChild')->will($this->returnValue('ThenChild'));
        $this->assertEquals('ThenChild', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function returnsAndRendersElseChildIfNoValidationResultsArePresentAtAll()
    {
        $this->viewHelper->expects($this->once())->method('renderElseChild')->will($this->returnValue('ElseChild'));
        $this->assertEquals('ElseChild', $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function queriesResultForPropertyIfPropertyPathIsGiven()
    {
        $resultMock = $this->createMock(\Neos\Error\Messages\Result::class);
        $resultMock->expects($this->once())->method('forProperty')->with('foo.bar.baz')->will($this->returnValue(new Result()));

        $this->request->expects($this->once())->method('getInternalArgument')->with('__submittedArgumentValidationResults')->will($this->returnValue($resultMock));

        $this->viewHelper->setArguments(['for' => 'foo.bar.baz']);
        $this->viewHelper->render();
    }
}
