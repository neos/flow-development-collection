<?php
namespace TYPO3\Fluid\Tests\Unit\Core\Parser\SyntaxTree;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\Controller\ControllerContext;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Fluid\Core\Parser\Fixtures\ChildNodeAccessFacetViewHelper;
use TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode;
use TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3\Fluid\Core\Rendering\RenderingContext;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

require_once(__DIR__ . '/../Fixtures/ChildNodeAccessFacetViewHelper.php');
require_once(__DIR__ . '/../../Fixtures/TestViewHelper.php');

/**
 * Testcase for \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
 */
class ViewHelperNodeTest extends UnitTestCase
{
    /**
     * @var RenderingContext
     */
    protected $renderingContext;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var TemplateVariableContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $templateVariableContainer;

    /**
     * @var ControllerContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockControllerContext;

    /**
     * @var ViewHelperVariableContainer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockViewHelperVariableContainer;

    /**
     * Setup fixture
     */
    public function setUp()
    {
        $this->renderingContext = new RenderingContext();

        $this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();
        $this->inject($this->renderingContext, 'objectManager', $this->mockObjectManager);

        $this->templateVariableContainer = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer')->disableOriginalConstructor()->getMock();
        $this->inject($this->renderingContext, 'templateVariableContainer', $this->templateVariableContainer);

        $this->mockControllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();
        $this->renderingContext->setControllerContext($this->mockControllerContext);

        $this->mockViewHelperVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
        $this->inject($this->renderingContext, 'viewHelperVariableContainer', $this->mockViewHelperVariableContainer);
    }

    /**
     * @test
     */
    public function constructorSetsViewHelperAndArguments()
    {
        $viewHelper = $this->createMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper');
        $arguments = array('foo' => 'bar');
        /** @var ViewHelperNode|\PHPUnit_Framework_MockObject_MockObject $viewHelperNode */
        $viewHelperNode = $this->getAccessibleMock('TYPO3\Fluid\Core\Parser\SyntaxTree\ViewHelperNode', array('dummy'), array($viewHelper, $arguments));

        $this->assertEquals(get_class($viewHelper), $viewHelperNode->getViewHelperClassName());
        $this->assertEquals($arguments, $viewHelperNode->_get('arguments'));
    }

    /**
     * @test
     */
    public function childNodeAccessFacetWorksAsExpected()
    {
        /** @var TextNode|\PHPUnit_Framework_MockObject_MockObject $childNode */
        $childNode = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\SyntaxTree\TextNode')->setConstructorArgs(array('foo'))->getMock();

        /** @var ChildNodeAccessFacetViewHelper|\PHPUnit_Framework_MockObject_MockObject $mockViewHelper */
        $mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\Parser\Fixtures\ChildNodeAccessFacetViewHelper')->setMethods(array('setChildNodes', 'initializeArguments', 'render', 'prepareArguments'))->getMock();

        $viewHelperNode = new ViewHelperNode($mockViewHelper, array());
        $viewHelperNode->addChildNode($childNode);

        $mockViewHelper->expects($this->once())->method('setChildNodes')->with($this->equalTo(array($childNode)));

        $viewHelperNode->evaluate($this->renderingContext);
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderIsCalledByViewHelperNode()
    {
        /** @var AbstractViewHelper|\PHPUnit_Framework_MockObject_MockObject $mockViewHelper */
        $mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper')->setMethods(array('initializeArgumentsAndRender', 'prepareArguments'))->getMock();
        $mockViewHelper->expects($this->once())->method('initializeArgumentsAndRender');

        $viewHelperNode = new ViewHelperNode($mockViewHelper, array());

        $viewHelperNode->evaluate($this->renderingContext);
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderIsCalledWithCorrectArguments()
    {
        $arguments = array(
            'param0' => new ArgumentDefinition('param1', 'string', 'Hallo', true, null, false),
            'param1' => new ArgumentDefinition('param1', 'string', 'Hallo', true, null, true),
            'param2' => new ArgumentDefinition('param2', 'string', 'Hallo', true, null, true)
        );

        /** @var AbstractViewHelper|\PHPUnit_Framework_MockObject_MockObject $mockViewHelper */
        $mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper')->setMethods(array('initializeArgumentsAndRender', 'prepareArguments'))->getMock();
        $mockViewHelper->expects($this->any())->method('prepareArguments')->will($this->returnValue($arguments));
        $mockViewHelper->expects($this->once())->method('initializeArgumentsAndRender');

        $viewHelperNode = new ViewHelperNode($mockViewHelper, array(
            'param2' => new TextNode('b'),
            'param1' => new TextNode('a')
        ));

        $viewHelperNode->evaluate($this->renderingContext);
    }

    /**
     * @test
     */
    public function evaluateMethodPassesRenderingContextToViewHelper()
    {
        /** @var AbstractViewHelper|\PHPUnit_Framework_MockObject_MockObject $mockViewHelper */
        $mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper')->setMethods(array('render', 'validateArguments', 'prepareArguments', 'setRenderingContext'))->getMock();
        $mockViewHelper->expects($this->once())->method('setRenderingContext')->with($this->renderingContext);

        $viewHelperNode = new ViewHelperNode($mockViewHelper, array());

        $viewHelperNode->evaluate($this->renderingContext);
    }

    /**
     * @test
     */
    public function multipleEvaluateCallsShareTheSameViewHelperInstance()
    {
        /** @var AbstractViewHelper|\PHPUnit_Framework_MockObject_MockObject $mockViewHelper */
        $mockViewHelper = $this->getMockBuilder('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper')->setMethods(array('render', 'validateArguments', 'prepareArguments', 'setViewHelperVariableContainer'))->getMock();
        $mockViewHelper->expects($this->exactly(2))->method('render')->will($this->returnValue('String'));

        $viewHelperNode = new ViewHelperNode($mockViewHelper, array());

        $viewHelperNode->evaluate($this->renderingContext);
        $viewHelperNode->evaluate($this->renderingContext);

        // dummy assertion to avoid "risky test" warning
        $this->assertTrue(true);
    }
}
