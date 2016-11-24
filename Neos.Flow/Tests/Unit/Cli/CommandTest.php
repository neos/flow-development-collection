<?php
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
use Neos\Flow\Command\CacheCommandController;
use Neos\Flow\Reflection\MethodReflection;
use Neos\Flow\Reflection\ParameterReflection;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the CLI Command class
 */
class CommandTest extends UnitTestCase
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
    public function setUp()
    {
        $this->command = $this->getAccessibleMock(Cli\Command::class, ['getCommandMethodReflection'], [], '', false);
        $this->methodReflection = $this->createMock(MethodReflection::class, [], [__CLASS__, 'dummyMethod']);
        $this->command->expects($this->any())->method('getCommandMethodReflection')->will($this->returnValue($this->methodReflection));
    }

    /**
     * Method used to construct some test objects locally
     * @param string $arg
     */
    public function dummyMethod($arg)
    {
    }

    /**
     * @return array
     */
    public function commandIdentifiers()
    {
        return [
            [CacheCommandController::class, 'flush', 'neos.flow:cache:flush'],
            ['RobertLemke\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'robertlemke.foo.faa.fuuum:coffee:brew'],
            ['SomePackage\Command\CookieCommandController', 'bake', 'somepackage:cookie:bake']
        ];
    }

    /**
     * @test
     * @dataProvider commandIdentifiers
     */
    public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier)
    {
        $command = new Cli\Command($controllerClassName, $commandName);
        $this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
    }

    /**
     * @test
     */
    public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments()
    {
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([]));
        $this->assertFalse($this->command->hasArguments());
    }

    /**
     * @test
     */
    public function hasArgumentsReturnsTrueIfCommandExpectsArguments()
    {
        $parameterReflection = $this->createMock(ParameterReflection::class, [], [[__CLASS__, 'dummyMethod'], 'arg']);
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([$parameterReflection]));
        $this->assertTrue($this->command->hasArguments());
    }

    /**
     * @test
     */
    public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments()
    {
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([]));
        $this->assertSame([], $this->command->getArgumentDefinitions());
    }

    /**
     * @test
     */
    public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments()
    {
        $parameterReflection = $this->createMock(ParameterReflection::class, [], [[__CLASS__, 'dummyMethod'], 'arg']);
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockMethodParameters = ['argument1' => ['optional' => false], 'argument2' => ['optional' => true]];
        $mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue($mockMethodParameters));
        $this->command->injectReflectionService($mockReflectionService);
        $this->methodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue([$parameterReflection]));
        $this->methodReflection->expects($this->atLeastOnce())->method('getTagsValues')->will($this->returnValue(['param' => ['@param $argument1 argument1 description', '@param $argument2 argument2 description']]));

        $expectedResult = [
            new Cli\CommandArgumentDefinition('argument1', true, 'argument1 description'),
            new Cli\CommandArgumentDefinition('argument2', false, 'argument2 description')
        ];
        $actualResult = $this->command->getArgumentDefinitions();
        $this->assertEquals($expectedResult, $actualResult);
    }
}
