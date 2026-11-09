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
use Neos\Flow\Cli\CommandManager;
use Neos\Flow\Command\HelpCommandController;
use Neos\Flow\Mvc\Exception\InvalidArgumentMixingException;
use Neos\Flow\Mvc\Exception\NoSuchCommandException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Utility\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the MVC CLI Request Builder
 */
final class RequestBuilderTest extends UnitTestCase
{
    /**
     * @var Cli\RequestBuilder
     */
    protected $requestBuilder;

    /**
     * @var ObjectManagerInterface
     */
    protected $mockObjectManager;

    /**
     * @var Cli\CommandManager
     */
    protected $mockCommandManager;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('getObjectNameByClassName')->with('Acme\Test\Command\DefaultCommandController')->willReturn(('Acme\Test\Command\DefaultCommandController'));

        $mockCommand = $this->createMock(Command::class);
        $mockCommand->method('getControllerClassName')->willReturn(('Acme\Test\Command\DefaultCommandController'));
        $mockCommand->method('getControllerCommandName')->willReturn(('list'));

        $this->mockCommandManager = $this->createMock(CommandManager::class);
        $this->mockCommandManager->method('getCommandByIdentifier')->with('acme.test:default:list')->willReturn(($mockCommand));

        $mockEnvironment = $this->createMock(Environment::class);
        $mockPackageManager = $this->createMock(PackageManager::class);

        $this->requestBuilder = new Cli\RequestBuilder($mockEnvironment, $this->mockObjectManager, $mockPackageManager, $this->mockCommandManager);
    }

    /**
     * Checks if a CLI request specifying a package, controller and action name results in the expected request object
     */
    #[Test]
    public function cliAccessWithPackageControllerAndActionNameBuildsCorrectRequest(): void
    {
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->willReturn(([]));

        $request = $this->requestBuilder->build('acme.test:default:list');
        self::assertSame('Acme\Test\Command\DefaultCommandController', $request->getControllerObjectName());
        self::assertSame('list', $request->getControllerCommandName(), 'The CLI request specifying a package, controller and action name did not return a request object pointing to the expected action.');
    }

    #[Test]
    public function ifCommandCantBeResolvedTheHelpScreenIsShown(): void
    {
        $this->mockCommandManager->method('getCommandByIdentifier')->with('test:default:list')->willThrowException(new NoSuchCommandException());

        $request = $this->requestBuilder->build('test:default:list');
        self::assertSame(HelpCommandController::class, $request->getControllerObjectName());
    }

    /**
     * Checks if a CLI request specifying some "console style" (--my-argument=value) arguments results in the expected request object
     */
    #[Test]
    public function cliAccessWithPackageControllerActionAndArgumentsBuildsCorrectRequest(): void
    {
        $methodParameters = [
            'testArgument' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string']
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value --test-argument2=value2');
        self::assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        self::assertSame('value', $request->getArgument('testArgument'), 'The "testArgument" had not the given value.');
        self::assertSame('value2', $request->getArgument('testArgument2'), 'The "testArgument2" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some "console style" (--my-argument =value) arguments with spaces between name and value results in the expected request object
     */
    #[Test]
    public function checkIfCliAccesWithPackageControllerActionAndArgumentsToleratesSpaces(): void
    {
        $methodParameters = [
            'testArgument' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string'],
            'testArgument3' => ['optional' => false, 'type' => 'string'],
            'testArgument4' => ['optional' => false, 'type' => 'string']
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument= value --test-argument2 =value2 --test-argument3 = value3 --test-argument4=value4');
        self::assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument3'), 'The given "testArgument3" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument4'), 'The given "testArgument4" was not found in the built request.');
        self::assertSame('value', $request->getArgument('testArgument'), 'The "testArgument" had not the given value.');
        self::assertSame('value2', $request->getArgument('testArgument2'), 'The "testArgument2" had not the given value.');
        self::assertSame('value3', $request->getArgument('testArgument3'), 'The "testArgument3" had not the given value.');
        self::assertSame('value4', $request->getArgument('testArgument4'), 'The "testArgument4" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some short "console style" (-c value or -c=value or -c = value) arguments results in the expected request object
     */
    #[Test]
    public function CliAccesWithShortArgumentsBuildsCorrectRequest(): void
    {
        $methodParameters = [
            'a' => ['optional' => false, 'type' => 'string'],
            'd' => ['optional' => false, 'type' => 'string'],
            'f' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list -d valued -f=valuef -a = valuea');
        self::assertTrue($request->hasArgument('d'), 'The given "d" was not found in the built request.');
        self::assertTrue($request->hasArgument('f'), 'The given "f" was not found in the built request.');
        self::assertTrue($request->hasArgument('a'), 'The given "a" was not found in the built request.');
        self::assertSame('valued', $request->getArgument('d'), 'The "d" had not the given value.');
        self::assertSame('valuef', $request->getArgument('f'), 'The "f" had not the given value.');
        self::assertSame('valuea', $request->getArgument('a'), 'The "a" had not the given value.');
    }

    /**
     * Checks if a CLI request specifying some mixed "console style" (-c or --my-argument -f=value) arguments with and
     * without values results in the expected request object
     */
    #[Test]
    public function CliAccesWithArgumentsWithAndWithoutValuesBuildsCorrectRequest(): void
    {
        $methodParameters = [
            'testArgument' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string'],
            'testArgument3' => ['optional' => false, 'type' => 'string'],
            'testArgument4' => ['optional' => false, 'type' => 'string'],
            'testArgument5' => ['optional' => false, 'type' => 'string'],
            'testArgument6' => ['optional' => false, 'type' => 'string'],
            'testArgument7' => ['optional' => false, 'type' => 'string'],
            'f' => ['optional' => false, 'type' => 'string'],
            'd' => ['optional' => false, 'type' => 'string'],
            'a' => ['optional' => false, 'type' => 'string'],
            'c' => ['optional' => false, 'type' => 'string'],
            'j' => ['optional' => false, 'type' => 'string'],
            'k' => ['optional' => false, 'type' => 'string'],
            'm' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value --test-argument2= value2 -k --test-argument-3 = value3 --test-argument4=value4 -f valuef -d=valued -a = valuea -c --testArgument7 --test-argument5 = 5 --test-argument6 -j kjk -m');
        self::assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument2'), 'The given "testArgument2" was not found in the built request.');
        self::assertTrue($request->hasArgument('k'), 'The given "k" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument3'), 'The given "testArgument3" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument4'), 'The given "testArgument4" was not found in the built request.');
        self::assertTrue($request->hasArgument('f'), 'The given "f" was not found in the built request.');
        self::assertTrue($request->hasArgument('d'), 'The given "d" was not found in the built request.');
        self::assertTrue($request->hasArgument('a'), 'The given "a" was not found in the built request.');
        self::assertTrue($request->hasArgument('c'), 'The given "d" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument7'), 'The given "testArgument7" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument5'), 'The given "testArgument5" was not found in the built request.');
        self::assertTrue($request->hasArgument('testArgument6'), 'The given "testArgument6" was not found in the built request.');
        self::assertTrue($request->hasArgument('j'), 'The given "j" was not found in the built request.');
        self::assertTrue($request->hasArgument('m'), 'The given "m" was not found in the built request.');
        self::assertSame('value', $request->getArgument('testArgument'), 'The "testArgument" had not the given value.');
        self::assertSame('value2', $request->getArgument('testArgument2'), 'The "testArgument2" had not the given value.');
        self::assertSame('value3', $request->getArgument('testArgument3'), 'The "testArgument3" had not the given value.');
        self::assertSame('value4', $request->getArgument('testArgument4'), 'The "testArgument4" had not the given value.');
        self::assertSame('valuef', $request->getArgument('f'), 'The "f" had not the given value.');
        self::assertSame('valued', $request->getArgument('d'), 'The "d" had not the given value.');
        self::assertSame('valuea', $request->getArgument('a'), 'The "a" had not the given value.');
        self::assertSame('5', $request->getArgument('testArgument5'), 'The "testArgument4" had not the given value.');
        self::assertSame('kjk', $request->getArgument('j'), 'The "j" had not the given value.');
    }

    #[Test]
    public function argumentWithValueSeparatedByEqualSignBuildsCorrectRequest(): void
    {
        $methodParameters = [
            'testArgument' => ['optional' => false, 'type' => 'string']
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument=value');
        self::assertTrue($request->hasArgument('testArgument'), 'The given "testArgument" was not found in the built request.');
        self::assertSame('value', $request->getArgument('testArgument'), 'The "testArgument" had not the given value.');
    }

    #[Test]
    public function insteadOfNamedArgumentsTheArgumentsCanBePassedUnnamedInTheCorrectOrder(): void
    {
        $methodParameters = [
            'testArgument1' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue --test-argument2 secondArgumentValue');
        self::assertSame('firstArgumentValue', $request->getArgument('testArgument1'));
        self::assertSame('secondArgumentValue', $request->getArgument('testArgument2'));

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue');
        self::assertSame('firstArgumentValue', $request->getArgument('testArgument1'));
        self::assertSame('secondArgumentValue', $request->getArgument('testArgument2'));
    }

    #[Test]
    public function argumentsAreDetectedAfterOptions(): void
    {
        $methodParameters = [
            'some' => ['optional' => true, 'type' => 'boolean'],
            'option' => ['optional' => true, 'type' => 'string'],
            'argument1' => ['optional' => false, 'type' => 'string'],
            'argument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list --some -option=value file1 file2');
        self::assertSame('list', $request->getControllerCommandName());
        self::assertTrue($request->getArgument('some'));
        self::assertSame('file1', $request->getArgument('argument1'));
        self::assertSame('file2', $request->getArgument('argument2'));
    }

    #[Test]
    public function exceedingArgumentsMayBeSpecified(): void
    {
        $methodParameters = [
            'testArgument1' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedArguments = ['testArgument1' => 'firstArgumentValue', 'testArgument2' => 'secondArgumentValue'];

        $request = $this->requestBuilder->build('acme.test:default:list --test-argument1=firstArgumentValue --test-argument2 secondArgumentValue exceedingArgument1');
        self::assertSame($expectedArguments, $request->getArguments());
        self::assertSame(['exceedingArgument1'], $request->getExceedingArguments());
    }

    #[Test]
    public function ifNamedArgumentsAreUsedAllRequiredArgumentsMustBeNamed(): void
    {
        $this->expectException(InvalidArgumentMixingException::class);
        $methodParameters = [
            'testArgument1' => ['optional' => false, 'type' => 'string'],
            'testArgument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $this->requestBuilder->build('acme.test:default:list --test-argument1 firstArgumentValue secondArgumentValue');
    }

    #[Test]
    public function ifUnnamedArgumentsAreUsedAllRequiredArgumentsMustBeUnnamed(): void
    {
        $this->expectException(InvalidArgumentMixingException::class);
        $methodParameters = [
            'requiredArgument1' => ['optional' => false, 'type' => 'string'],
            'requiredArgument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $this->requestBuilder->build('acme.test:default:list firstArgumentValue --required-argument2 secondArgumentValue');
    }

    #[Test]
    public function booleanOptionsAreConsideredEvenIfAnUnnamedArgumentFollows(): void
    {
        $methodParameters = [
            'requiredArgument1' => ['optional' => false, 'type' => 'string'],
            'requiredArgument2' => ['optional' => false, 'type' => 'string'],
            'booleanOption' => ['optional' => true, 'type' => 'boolean'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedArguments = ['requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue', 'booleanOption' => true];

        $request = $this->requestBuilder->build('acme.test:default:list --booleanOption firstArgumentValue secondArgumentValue');
        self::assertEquals($expectedArguments, $request->getArguments());
    }

    #[Test]
    public function optionsAreNotMappedToCommandArgumentsIfTheyAreUnnamed(): void
    {
        $methodParameters = [
            'requiredArgument1' => ['optional' => false, 'type' => 'string'],
            'requiredArgument2' => ['optional' => false, 'type' => 'string'],
            'booleanOption' => ['optional' => true, 'type' => 'boolean'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedArguments = ['requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => 'secondArgumentValue'];

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
        self::assertSame($expectedArguments, $request->getArguments());
    }

    #[Test]
    public function afterAllRequiredArgumentsUnnamedParametersAreStoredAsExceedingArguments(): void
    {
        $methodParameters = [
            'requiredArgument1' => ['optional' => false, 'type' => 'string'],
            'requiredArgument2' => ['optional' => false, 'type' => 'string'],
            'booleanOption' => ['optional' => true, 'type' => 'boolean'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedExceedingArguments = ['true'];

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue secondArgumentValue true');
        self::assertSame($expectedExceedingArguments, $request->getExceedingArguments());
    }

    #[Test]
    public function booleanOptionsCanHaveOnlyCertainValuesIfTheValueIsAssignedWithoutEqualSign(): void
    {
        $methodParameters = [
            'b1' => ['optional' => true, 'type' => 'boolean'],
            'b2' => ['optional' => true, 'type' => 'boolean'],
            'b3' => ['optional' => true, 'type' => 'boolean'],
            'b4' => ['optional' => true, 'type' => 'boolean'],
            'b5' => ['optional' => true, 'type' => 'boolean'],
            'b6' => ['optional' => true, 'type' => 'boolean'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedArguments = ['b1' => true, 'b2' => true, 'b3' => true, 'b4' => false, 'b5' => false, 'b6' => false];

        $request = $this->requestBuilder->build('acme.test:default:list --b2 y --b1 1 --b3 true --b4 false --b5 n --b6 0');
        self::assertEquals($expectedArguments, $request->getArguments());
    }

    /**
     * Data provider
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function quotedValues(): \Iterator
    {
        yield ["'value with spaces'", 'value with spaces'];
        yield ["'value with spaces and \\' escaped'", 'value with spaces and \' escaped'];
        yield ['"value with spaces"', 'value with spaces'];
        yield ['"value with spaces and \\" escaped"', 'value with spaces and " escaped'];
        yield ['value\\ with\\ spaces', 'value with spaces'];
        yield ['no\\"spaces\\\'here', 'no"spaces\'here'];
        yield ["nospaces\\'here", "nospaces'here"];
        yield ['no\\"spaceshere', 'no"spaceshere'];
        yield ['no\\\\spaceshere', 'no\\spaceshere'];
        yield ["''", ''];
    }

    #[DataProvider('quotedValues')]
    #[Test]
    public function quotedArgumentValuesAreCorrectlyParsedWhenPassingTheCommandAsString($quotedArgument, $expectedResult): void
    {
        $methodParameters = [
            'requiredArgument1' => ['optional' => false, 'type' => 'string'],
            'requiredArgument2' => ['optional' => false, 'type' => 'string'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $expectedArguments = ['requiredArgument1' => 'firstArgumentValue', 'requiredArgument2' => $expectedResult];

        $request = $this->requestBuilder->build('acme.test:default:list firstArgumentValue ' . $quotedArgument);
        self::assertEquals($expectedArguments, $request->getArguments());
    }

    /**
     * Data provider
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function arrayCliArgumentValues(): \Iterator
    {
        yield [
            '--a1 1 --a2 y --a1 x --a2 z',
            ['a1' => ['1', 'x'], 'a2' => ['y', 'z']],
            []
        ];
        yield [
            '--a1 1 --a2 y --a1 x --a2 z foo bar',
            ['a1' => ['1', 'x'], 'a2' => ['y', 'z']],
            ['foo', 'bar']
        ];
        yield [
            '--a1 1 --a1 x foo bar',
            ['a1' => ['1', 'x']],
            ['foo', 'bar']
        ];
    }

    #[DataProvider('arrayCliArgumentValues')]
    #[Test]
    public function arrayArgumentIsParsedCorrectly(string $cliArguments, array $expectedArguments, array $epectedExceedingArguments): void
    {
        $methodParameters = [
            'a1' => ['optional' => false, 'type' => 'array'],
            'a2' => ['optional' => true, 'type' => 'array'],
        ];
        $this->mockCommandManager->expects($this->once())->method('getCommandMethodParameters')->with('Acme\Test\Command\DefaultCommandController', 'listCommand')->willReturn(($methodParameters));

        $request = $this->requestBuilder->build('acme.test:default:list ' . $cliArguments);
        self::assertEquals($expectedArguments, $request->getArguments());
        self::assertEquals($epectedExceedingArguments, $request->getExceedingArguments());
    }
}
