<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Cli;
use Neos\Flow\Cli\Command;
use Neos\Flow\Cli\CommandArgumentDefinition;
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\ParameterReflection;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\Unit\Cli\Fixtures\Command\MockACommandController;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the CLI Command class
 */
final class CommandTest extends UnitTestCase
{
    /**
     * @var Cli\Command
     */
    protected $command;

    /**
     * @var MethodReflection
     */
    protected $methodReflection;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->command = $this->getAccessibleMock(Command::class, ['getCommandMethodReflection'], [], '', false);
        $this->methodReflection = $this->createMock(MethodReflection::class, [], [__CLASS__, 'dummyMethod']);
        $this->command->method('getCommandMethodReflection')->willReturn($this->methodReflection);
    }

    /**
     * Method used to construct some test objects locally
     * @param string $arg
     */
    public function dummyMethod($arg): void
    {
    }

    /**
     * @return array
     */
    public static function commandIdentifiers(): array
    {
        return [
            [CacheCommandController::class, 'flush', 'neos.flow:cache:flush'],
            ['RobertLemke\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'robertlemke.foo.faa.fuuum:coffee:brew'],
            ['SomePackage\Command\CookieCommandController', 'bake', 'somepackage:cookie:bake']
        ];
    }

    #[DataProvider('commandIdentifiers')]
    #[Test]
    public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier): void
    {
        $command = new Command($controllerClassName, $commandName);
        self::assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
    }

    #[Test]
    public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments(): void
    {
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->willReturn(([]));
        self::assertFalse($this->command->hasArguments());
    }

    #[Test]
    public function hasArgumentsReturnsTrueIfCommandExpectsArguments(): void
    {
        $parameterReflection = $this->createStub(ParameterReflection::class, [], [[__CLASS__, 'dummyMethod'], 'arg']);
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->willReturn(([$parameterReflection]));
        self::assertTrue($this->command->hasArguments());
    }

    #[Test]
    public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments(): void
    {
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->willReturn(([]));
        self::assertSame([], $this->command->getArgumentDefinitions());
    }

    #[Test]
    public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments(): void
    {
        $parameterReflection = $this->createStub(ParameterReflection::class, [], [[__CLASS__, 'dummyMethod'], 'arg']);
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockMethodParameters = ['argument1' => ['optional' => false], 'argument2' => ['optional' => true]];
        $mockReflectionService->expects(self::atLeastOnce())->method('getMethodParameters')->willReturn($mockMethodParameters);

        $this->command->injectReflectionService($mockReflectionService);
        $this->inject($this->command, 'controllerClassName', MockACommandController::class);

        $this->methodReflection->expects(self::atLeastOnce())->method('getParameters')->willReturn([$parameterReflection]);
        $this->methodReflection->expects(self::atLeastOnce())->method('getTagsValues')->willReturn(['param' => ['@param $argument1 argument1 description', '@param $argument2 argument2 description']]);

        $expectedResult = [
            new CommandArgumentDefinition('argument1', true, 'argument1 description'),
            new CommandArgumentDefinition('argument2', false, 'argument2 description')
        ];
        $actualResult = $this->command->getArgumentDefinitions();
        self::assertEquals($expectedResult, $actualResult);
    }

    #[Test]
    public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArgumentsEvenWhenDocBlocksAreMissing(): void
    {
        $parameterReflection = $this->createStub(ParameterReflection::class, [], [[__CLASS__, 'dummyMethod'], 'arg']);
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockMethodParameters = ['argument1' => ['optional' => false], 'argument2' => ['optional' => true]];
        $mockReflectionService->expects(self::atLeastOnce())->method('getMethodParameters')->willReturn($mockMethodParameters);
        $this->command->injectReflectionService($mockReflectionService);
        $this->inject($this->command, 'controllerClassName', MockACommandController::class);
        $this->methodReflection->expects(self::atLeastOnce())->method('getParameters')->willReturn([$parameterReflection]);
        $this->methodReflection->expects(self::atLeastOnce())->method('getTagsValues')->willReturn([]);

        $expectedResult = [
            new CommandArgumentDefinition('argument1', true, 'argument1'),
            new CommandArgumentDefinition('argument2', false, 'argument2')
        ];
        $actualResult = $this->command->getArgumentDefinitions();
        self::assertEquals($expectedResult, $actualResult);
    }
}
