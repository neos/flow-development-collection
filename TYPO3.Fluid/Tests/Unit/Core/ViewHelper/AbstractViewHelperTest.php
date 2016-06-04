<?php
namespace TYPO3\Fluid\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the TYPO3.Fluid package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

require_once(__DIR__ . '/../Fixtures/TestViewHelper.php');
require_once(__DIR__ . '/../Fixtures/TestViewHelper2.php');

/**
 * Testcase for AbstractViewHelper
 *
 */
class AbstractViewHelperTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Reflection\ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var array
     */
    protected $fixtureMethodParameters = array(
        'param1' => array(
            'position' => 0,
            'optional' => false,
            'type' => 'integer',
            'defaultValue' => null
        ),
        'param2' => array(
            'position' => 1,
            'optional' => false,
            'type' => 'array',
            'array' => true,
            'defaultValue' => null
        ),
        'param3' => array(
            'position' => 2,
            'optional' => true,
            'type' => 'string',
            'array' => false,
            'defaultValue' => 'default'
        ),
    );

    /**
     * @var array
     */
    protected $fixtureMethodTags = array(
        'param' => array(
            'integer $param1 P1 Stuff',
            'array $param2 P2 Stuff',
            'string $param3 P3 Stuff'
        )
    );

    public function setUp()
    {
        $this->mockReflectionService = $this->getMockBuilder('TYPO3\Flow\Reflection\ReflectionService')->disableOriginalConstructor()->getMock();
        $this->mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Reflection\ReflectionService')->will($this->returnValue($this->mockReflectionService));
    }

    /**
     * @test
     */
    public function argumentsCanBeRegistered()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $expected = new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $this->assertEquals(array($name => $expected), $viewHelper->prepareArguments(), 'Argument definitions not returned correctly.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function registeringTheSameArgumentNameAgainThrowsException()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render'), array(), '', false);

        $name = 'shortName';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('registerArgument', $name, 'integer', $description, $isRequired);
    }

    /**
     * @test
     */
    public function overrideArgumentOverwritesExistingArgumentDefinition()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $name = 'argumentName';
        $description = 'argument description';
        $overriddenDescription = 'overwritten argument description';
        $type = 'string';
        $overriddenType = 'integer';
        $isRequired = true;
        $expected = new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition($name, $overriddenType, $overriddenDescription, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('overrideArgument', $name, $overriddenType, $overriddenDescription, $isRequired);
        $this->assertEquals($viewHelper->prepareArguments(), array($name => $expected), 'Argument definitions not returned correctly. The original ArgumentDefinition could not be overridden.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Fluid\Core\ViewHelper\Exception
     */
    public function overrideArgumentThrowsExceptionWhenTryingToOverwriteAnNonexistingArgument()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->_call('overrideArgument', 'argumentName', 'string', 'description', true);
    }

    /**
     * @test
     */
    public function prepareArgumentsCallsInitializeArguments()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'initializeArguments'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects($this->once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithDescriptionIfDebugModeIsEnabled()
    {
        \TYPO3\Fluid\Fluid::$debugMode = true;

        $dataCacheMock = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\VariableFrontend')->disableOriginalConstructor()->getMock();
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue(array()));

        $viewHelper = new \TYPO3\Fluid\Core\Fixtures\TestViewHelper();

        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\Fluid\Core\Fixtures\TestViewHelper', 'render')->will($this->returnValue($this->fixtureMethodParameters));
        $this->mockReflectionService->expects($this->once())->method('getMethodTagsValues')->with('TYPO3\Fluid\Core\Fixtures\TestViewHelper', 'render')->will($this->returnValue($this->fixtureMethodTags));
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $expected = array(
            'param1' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param1', 'integer', 'P1 Stuff', true, null, true),
            'param2' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param2', 'array', 'P2 Stuff', true, null, true),
            'param3' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param3', 'string', 'P3 Stuff', false, 'default', true)
        );

        $this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');

        \TYPO3\Fluid\Fluid::$debugMode = false;
    }

    /**
     * @test
     */
    public function prepareArgumentsRegistersAnnotationBasedArgumentsWithoutDescriptionIfDebugModeIsDisabled()
    {
        \TYPO3\Fluid\Fluid::$debugMode = false;

        $dataCacheMock = $this->getMockBuilder('TYPO3\Flow\Cache\Frontend\VariableFrontend')->disableOriginalConstructor()->getMock();
        $dataCacheMock->expects($this->any())->method('has')->will($this->returnValue(true));
        $dataCacheMock->expects($this->any())->method('get')->will($this->returnValue(array()));

        $viewHelper = new \TYPO3\Fluid\Core\Fixtures\TestViewHelper2();

        $this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\Fluid\Core\Fixtures\TestViewHelper2', 'render')->will($this->returnValue($this->fixtureMethodParameters));
        $this->mockReflectionService->expects($this->never())->method('getMethodTagsValues');
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $expected = array(
            'param1' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param1', 'integer', '', true, null, true),
            'param2' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param2', 'array', '', true, null, true),
            'param3' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('param3', 'string', '', false, 'default', true),
        );

        $this->assertEquals($expected, $viewHelper->prepareArguments(), 'Annotation based arguments were not registered.');
    }

    /**
     * @test
     */
    public function validateArgumentsCallsPrepareArguments()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array()));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'prepareArguments'), array(), '', false);

        $viewHelper->setArguments(array('test' => new \ArrayObject));
        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array('test' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'array', false, 'documentation'))));
        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidators()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(array('test' => 'Value of argument'));

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
            'test' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'string', false, 'documentation')
        )));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'prepareArguments'), array(), '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(array('test' => 'test'));

        $viewHelper->expects($this->once())->method('prepareArguments')->will($this->returnValue(array(
            'test' => new \TYPO3\Fluid\Core\ViewHelper\ArgumentDefinition('test', 'stdClass', false, 'documentation')
        )));

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheCorrectSequenceOfMethods()
    {
        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('validateArguments', 'initialize', 'callRenderMethod'));
        $viewHelper->expects($this->at(0))->method('validateArguments');
        $viewHelper->expects($this->at(1))->method('initialize');
        $viewHelper->expects($this->at(2))->method('callRenderMethod')->will($this->returnValue('Output'));

        $expectedOutput = 'Output';
        $actualOutput = $viewHelper->initializeArgumentsAndRender(array('argument1' => 'value1'));
        $this->assertEquals($expectedOutput, $actualOutput);
    }

    /**
     * @test
     */
    public function setRenderingContextShouldSetInnerVariables()
    {
        $templateVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer');
        $viewHelperVariableContainer = $this->createMock('TYPO3\Fluid\Core\ViewHelper\ViewHelperVariableContainer');
        $controllerContext = $this->getMockBuilder('TYPO3\Flow\Mvc\Controller\ControllerContext')->disableOriginalConstructor()->getMock();

        $renderingContext = new \TYPO3\Fluid\Core\Rendering\RenderingContext();
        $renderingContext->injectTemplateVariableContainer($templateVariableContainer);
        $renderingContext->injectViewHelperVariableContainer($viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock('TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper', array('render', 'prepareArguments'), array(), '', false);

        $viewHelper->setRenderingContext($renderingContext);

        $this->assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        $this->assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        $this->assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
