<?php
namespace Neos\FluidAdaptor\Tests\Unit\Core\ViewHelper;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Neos\FluidAdaptor\Core\ViewHelper\TemplateVariableContainer;
use Neos\FluidAdaptor\View\TemplateView;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

require_once(__DIR__ . '/../Fixtures/TestViewHelper.php');
require_once(__DIR__ . '/../Fixtures/TestViewHelper2.php');

/**
 * Testcase for AbstractViewHelper
 *
 */
class AbstractViewHelperTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var array
     */
    protected $fixtureMethodParameters = [
        'param1' => [
            'position' => 0,
            'optional' => false,
            'type' => 'integer',
            'defaultValue' => null
        ],
        'param2' => [
            'position' => 1,
            'optional' => false,
            'type' => 'array',
            'array' => true,
            'defaultValue' => null
        ],
        'param3' => [
            'position' => 2,
            'optional' => true,
            'type' => 'string',
            'array' => false,
            'defaultValue' => 'default'
        ],
    ];

    /**
     * @var array
     */
    protected $fixtureMethodTags = [
        'param' => [
            'integer $param1 P1 Stuff',
            'array $param2 P2 Stuff',
            'string $param3 P3 Stuff'
        ]
    ];

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->getMockBuilder(ReflectionService::class)->disableOriginalConstructor()->getMock();
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
    }

    /**
     * @test
     */
    public function argumentsCanBeRegistered(): void
    {
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $expected = new ArgumentDefinition($name, $type, $description, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        self::assertEquals([$name => $expected], $viewHelper->prepareArguments(), 'Argument definitions not returned correctly.');
    }

    /**
     * @test
     */
    public function registeringTheSameArgumentNameAgainThrowsException(): void
    {
        $this->expectException(Exception::class);
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render'], [], '', false);

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
    public function overrideArgumentOverwritesExistingArgumentDefinition(): void
    {
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $name = 'argumentName';
        $description = 'argument description';
        $overriddenDescription = 'overwritten argument description';
        $type = 'string';
        $overriddenType = 'integer';
        $isRequired = true;
        $expected = new ArgumentDefinition($name, $overriddenType, $overriddenDescription, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        $viewHelper->_call('overrideArgument', $name, $overriddenType, $overriddenDescription, $isRequired);
        self::assertEquals($viewHelper->prepareArguments(), [$name => $expected], 'Argument definitions not returned correctly. The original ArgumentDefinition could not be overridden.');
    }

    /**
     * @test
     */
    public function overrideArgumentThrowsExceptionWhenTryingToOverwriteAnNonexistingArgument(): void
    {
        $this->expectException(Exception::class);
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->_call('overrideArgument', 'argumentName', 'string', 'description', true);
    }

    /**
     * @test
     */
    public function prepareArgumentsCallsInitializeArguments(): void
    {
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'initializeArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects(self::once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsPrepareArguments(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects(self::once())->method('prepareArguments')->willReturn([]);

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setArguments(['test' => new \ArrayObject]);
        $viewHelper->expects(self::once())->method('prepareArguments')->willReturn(['test' => new ArgumentDefinition('test', 'array', false, 'documentation')]);
        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidators(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(['test' => 'Value of argument']);

        $viewHelper->expects(self::once())->method('prepareArguments')->willReturn([
            'test' => new ArgumentDefinition('test', 'string', false, 'documentation')
        ]);

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(['test' => 'test']);

        $viewHelper->expects(self::once())->method('prepareArguments')->willReturn([
            'test' => new ArgumentDefinition('test', 'stdClass', false, 'documentation')
        ]);

        $viewHelper->validateArguments();
    }

    /**
     * @test
     */
    public function initializeArgumentsAndRenderCallsTheCorrectSequenceOfMethods(): void
    {
        $calls = [];
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['validateArguments', 'initialize', 'callRenderMethod']);
        $viewHelper->expects(self::atLeastOnce())->method('validateArguments')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'validateArguments';
        });
        $viewHelper->expects(self::atLeastOnce())->method('initialize')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'initialize';
        });
        $viewHelper->expects(self::atLeastOnce())->method('callRenderMethod')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'callRenderMethod';
            return 'Output';
        });

        $expectedOutput = 'Output';
        $actualOutput = $viewHelper->initializeArgumentsAndRender(['argument1' => 'value1']);
        self::assertEquals($expectedOutput, $actualOutput);
        self::assertEquals(['validateArguments', 'initialize', 'callRenderMethod'], $calls);
    }

    /**
     * @test
     */
    public function setRenderingContextShouldSetInnerVariables(): void
    {
        $templateVariableContainer = $this->createMock(TemplateVariableContainer::class);
        $viewHelperVariableContainer = $this->createMock(ViewHelperVariableContainer::class);
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();

        $dummyView = new TemplateView([]);
        $renderingContext = $dummyView->getRenderingContext();
        $renderingContext->setVariableProvider($templateVariableContainer);
        $renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['render', 'prepareArguments'], [], '', false);

        $viewHelper->setRenderingContext($renderingContext);

        self::assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        self::assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        self::assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
