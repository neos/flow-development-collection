<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;
use TYPO3Fluid\Fluid\Core\ViewHelper\ArgumentDefinition;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer;

require_once(__DIR__ . '/../Fixtures/TestViewHelper.php');
require_once(__DIR__ . '/../Fixtures/TestViewHelper2.php');

/**
 * Testcase for AbstractViewHelper
 *
 */
final class AbstractViewHelperTest extends UnitTestCase
{
    /**
     * @var ReflectionService
     */
    protected $mockReflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    protected function setUp(): void
    {
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(ReflectionService::class)->willReturn($this->mockReflectionService);
    }

    #[Test]
    public function argumentsCanBeRegistered(): void
    {
        $this->mockReflectionService->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, [], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $name = 'This is a name';
        $description = 'Example desc';
        $type = 'string';
        $isRequired = true;
        $expected = new ArgumentDefinition($name, $type, $description, $isRequired);

        $viewHelper->_call('registerArgument', $name, $type, $description, $isRequired);
        self::assertEquals([$name => $expected], $viewHelper->prepareArguments(), 'Argument definitions not returned correctly.');
    }

    #[Test]
    public function overrideArgumentOverwritesExistingArgumentDefinition(): void
    {
        $this->mockReflectionService->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, [], [], '', false);
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

    #[Test]
    public function overrideArgumentThrowsExceptionWhenTryingToOverwriteAnNonexistingArgument(): void
    {
        $this->expectException(Exception::class);
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, [], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->_call('overrideArgument', 'argumentName', 'string', 'description', true);
    }

    #[Test]
    public function prepareArgumentsCallsInitializeArguments(): void
    {
        $this->mockReflectionService->method('getMethodParameters')->willReturn([]);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['initializeArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects($this->once())->method('initializeArguments');

        $viewHelper->prepareArguments();
    }

    #[Test]
    public function validateArgumentsCallsPrepareArguments(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->expects($this->once())->method('prepareArguments')->willReturn([]);

        $viewHelper->validateArguments();
    }

    #[Test]
    public function validateArgumentsAcceptsAllObjectsImplemtingArrayAccessAsAnArray(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['prepareArguments'], [], '', false);

        $viewHelper->setArguments(['test' => new \ArrayObject()]);
        $viewHelper->expects($this->once())->method('prepareArguments')->willReturn(['test' => new ArgumentDefinition('test', 'array', false, 'documentation')]);
        $viewHelper->validateArguments();
    }

    #[Test]
    public function validateArgumentsCallsTheRightValidators(): void
    {
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(['test' => 'Value of argument']);

        $viewHelper->expects($this->once())->method('prepareArguments')->willReturn([
            'test' => new ArgumentDefinition('test', 'string', false, 'documentation')
        ]);

        $viewHelper->validateArguments();
    }

    #[Test]
    public function validateArgumentsCallsTheRightValidatorsAndThrowsExceptionIfValidationIsWrong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['prepareArguments'], [], '', false);
        $viewHelper->injectObjectManager($this->mockObjectManager);

        $viewHelper->setArguments(['test' => 'test']);

        $viewHelper->expects($this->once())->method('prepareArguments')->willReturn([
            'test' => new ArgumentDefinition('test', 'stdClass', false, 'documentation')
        ]);

        $viewHelper->validateArguments();
    }

    #[Test]
    public function initializeArgumentsAndRenderCallsTheCorrectSequenceOfMethods(): void
    {
        $calls = [];
        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['validateArguments', 'initialize', 'callRenderMethod']);
        $viewHelper->expects($this->atLeastOnce())->method('validateArguments')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'validateArguments';
        });
        $viewHelper->expects($this->atLeastOnce())->method('initialize')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'initialize';
        });
        $viewHelper->expects($this->atLeastOnce())->method('callRenderMethod')->willReturnCallback(function () use (&$calls) {
            $calls[] = 'callRenderMethod';
            return 'Output';
        });

        $expectedOutput = 'Output';
        $actualOutput = $viewHelper->initializeArgumentsAndRender(['argument1' => 'value1']);
        self::assertEquals($expectedOutput, $actualOutput);
        self::assertSame(['validateArguments', 'initialize', 'callRenderMethod'], $calls);
    }

    #[Test]
    public function setRenderingContextShouldSetInnerVariables(): void
    {
        $templateVariableContainer = $this->createStub(TemplateVariableContainer::class);
        $viewHelperVariableContainer = $this->createStub(ViewHelperVariableContainer::class);
        $controllerContext = $this->createStub(ControllerContext::class);

        $dummyView = new TemplateView([]);
        $renderingContext = $dummyView->getRenderingContext();
        $renderingContext->setVariableProvider($templateVariableContainer);
        $renderingContext->setViewHelperVariableContainer($viewHelperVariableContainer);
        $renderingContext->setControllerContext($controllerContext);

        $viewHelper = $this->getAccessibleMock(AbstractViewHelper::class, ['prepareArguments'], [], '', false);

        $viewHelper->setRenderingContext($renderingContext);

        self::assertSame($viewHelper->_get('templateVariableContainer'), $templateVariableContainer);
        self::assertSame($viewHelper->_get('viewHelperVariableContainer'), $viewHelperVariableContainer);
        self::assertSame($viewHelper->_get('controllerContext'), $controllerContext);
    }
}
