<?php
namespace TYPO3\Fluid\Tests\Unit\ViewHelpers;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CycleViewHelper
 */
class CycleViewHelperTest extends \TYPO3\Fluid\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\Fluid\ViewHelpers\CycleViewHelper
     */
    protected $viewHelper;

    public function setUp()
    {
        parent::setUp();
        $this->viewHelper = $this->getMockBuilder('TYPO3\Fluid\ViewHelpers\CycleViewHelper')->setMethods(array('renderChildren'))->getMock();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function renderAddsCurrentValueToTemplateVariableContainerAndRemovesItAfterRendering()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');

        $values = array('bar', 'Fluid');
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     */
    public function renderAddsFirstValueToTemplateVariableContainerAfterLastValue()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'bar');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $values = array('bar', 'Fluid');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     */
    public function viewHelperSupportsAssociativeArrays()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'Flow');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'Fluid');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'Flow');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $values = array('foo' => 'Flow', 'bar' => 'Fluid');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
        $this->viewHelper->render($values, 'innerVariable');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function renderThrowsExceptionWhenPassingObjectsToValuesThatAreNotTraversable()
    {
        $object = new \stdClass();

        $this->viewHelper->render($object, 'innerVariable');
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfValuesIsNull()
    {
        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

        $this->assertEquals('Child nodes', $this->viewHelper->render(null, 'foo'));
    }

    /**
     * @test
     */
    public function renderReturnsChildNodesIfValuesIsAnEmptyArray()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('foo', null);
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('foo');

        $this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Child nodes'));

        $this->assertEquals('Child nodes', $this->viewHelper->render(array(), 'foo'));
    }

    /**
     * @test
     */
    public function renderIteratesThroughElementsOfTraversableObjects()
    {
        $this->templateVariableContainer->expects($this->at(0))->method('add')->with('innerVariable', 'value1');
        $this->templateVariableContainer->expects($this->at(1))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(2))->method('add')->with('innerVariable', 'value2');
        $this->templateVariableContainer->expects($this->at(3))->method('remove')->with('innerVariable');
        $this->templateVariableContainer->expects($this->at(4))->method('add')->with('innerVariable', 'value1');
        $this->templateVariableContainer->expects($this->at(5))->method('remove')->with('innerVariable');

        $traversableObject = new \ArrayObject(array('key1' => 'value1', 'key2' => 'value2'));
        $this->viewHelper->render($traversableObject, 'innerVariable');
        $this->viewHelper->render($traversableObject, 'innerVariable');
        $this->viewHelper->render($traversableObject, 'innerVariable');
    }
}
