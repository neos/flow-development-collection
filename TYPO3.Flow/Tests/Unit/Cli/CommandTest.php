<?php
namespace TYPO3\Flow\Tests\Unit\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Cli\Command;

/**
 * Testcase for the CLI Command class
 */
class CommandTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Cli\Command
	 */
	protected $command;

	/**
	 * @var \TYPO3\Flow\Reflection\MethodReflection
	 */
	protected $mockMethodReflection;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->command = $this->getAccessibleMock('TYPO3\Flow\Cli\Command', array('getCommandMethodReflection'), array(), '', FALSE);
		$this->mockMethodReflection = $this->getMock('TYPO3\Flow\Reflection\MethodReflection', array(), array(), '', FALSE);
		$this->command->expects($this->any())->method('getCommandMethodReflection')->will($this->returnValue($this->mockMethodReflection));
	}

	/**
	 * @return array
	 */
	public function commandIdentifiers() {
		return array(
			array('TYPO3\Flow\Command\CacheCommandController', 'flush', 'typo3.flow:cache:flush'),
			array('RobertLemke\Foo\Faa\Fuuum\Command\CoffeeCommandController', 'brew', 'robertlemke.foo.faa.fuuum:coffee:brew'),
			array('SomePackage\Command\CookieCommandController', 'bake', 'somepackage:cookie:bake')
		);
	}

	/**
	 * @test
	 * @dataProvider commandIdentifiers
	 */
	public function constructRendersACommandIdentifierByTheGivenControllerAndCommandName($controllerClassName, $commandName, $expectedCommandIdentifier) {
		$command = new Command($controllerClassName, $commandName);
		$this->assertEquals($expectedCommandIdentifier, $command->getCommandIdentifier());
	}

	/**
	 * @test
	 */
	public function hasArgumentsReturnsFalseIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertFalse($this->command->hasArguments());
	}

	/**
	 * @test
	 */
	public function hasArgumentsReturnsTrueIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('TYPO3\Flow\Reflection\ParameterReflection', array(), array(), '', FALSE);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->assertTrue($this->command->hasArguments());
	}

	/**
	 * @test
	 */
	public function getArgumentDefinitionsReturnsEmptyArrayIfCommandExpectsNoArguments() {
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array()));
		$this->assertSame(array(), $this->command->getArgumentDefinitions());
	}

	/**
	 * @test
	 */
	public function getArgumentDefinitionsReturnsArrayOfArgumentDefinitionIfCommandExpectsArguments() {
		$mockParameterReflection = $this->getMock('TYPO3\Flow\Reflection\ParameterReflection', array(), array(), '', FALSE);
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockMethodParameters = array('argument1' => array('optional' => FALSE), 'argument2' => array('optional' => TRUE));
		$mockReflectionService->expects($this->atLeastOnce())->method('getMethodParameters')->will($this->returnValue($mockMethodParameters));
		$this->command->injectReflectionService($mockReflectionService);
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getParameters')->will($this->returnValue(array($mockParameterReflection)));
		$this->mockMethodReflection->expects($this->atLeastOnce())->method('getTagsValues')->will($this->returnValue(array('param' => array('@param $argument1 argument1 description', '@param $argument2 argument2 description'))));

		$expectedResult = array(
			new \TYPO3\Flow\Cli\CommandArgumentDefinition('argument1', TRUE, 'argument1 description'),
			new \TYPO3\Flow\Cli\CommandArgumentDefinition('argument2', FALSE, 'argument2 description')
		);
		$actualResult = $this->command->getArgumentDefinitions();
		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>