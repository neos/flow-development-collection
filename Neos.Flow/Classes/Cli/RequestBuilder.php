<?php
namespace Neos\Flow\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Command\HelpCommandController;
use Neos\Flow\Mvc\Exception\CommandException;
use Neos\Flow\Mvc\Exception\InvalidArgumentMixingException;
use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Utility\Environment;

/**
 * Builds a CLI request object from the raw command call
 *
 * @Flow\Scope("singleton")
 */
class RequestBuilder
{
    /**
     * This is used to parse the command line, when it's passed as a string
     */
    const ARGUMENT_MATCHING_EXPRESSION = '/     # An argument is either...
		\'(?P<SingleQuotes>                     # a single-quoted string
			(?:\\\\\'|[^\'])*                   # (internally: contains escaped single quotes or everything not being single quotes)
		)\'
		|"(?P<DoubleQuotes>                     # OR a double-quoted string
			(?:\\\"|[^"])*                      # (internally: contains escaped double quotes or everything not being double quotes)
		)"
		|(?P<NoQuotes>                          # OR a non-quoted string
			(?:
				\\\\[ "\']                      # (internally: either the backslash escape followed by space, single or double quote or another backslash,
				|[^\'" ]                        #  or all other characters than the above ones)
			)+
		)
		/x';

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @param Environment $environment
     * @return void
     */
    public function injectEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param PackageManager $packageManager
     * @return void
     */
    public function injectPackageManager(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param CommandManager $commandManager
     * @return void
     */
    public function injectCommandManager(CommandManager $commandManager)
    {
        $this->commandManager = $commandManager;
    }

    /**
     * Builds a CLI request object from a command line.
     *
     * The given command line may be a string (e.g. "mypackage:foo do-that-thing --force") or
     * an array consisting of the individual parts. The array must not include the script
     * name (like in $argv) but start with command right away.
     *
     * @param mixed $commandLine The command line, either as a string or as an array
     * @return Request The CLI request as an object
     * @throws InvalidArgumentMixingException
     * @throws InvalidArgumentNameException
     */
    public function build($commandLine): Request
    {
        $request = new Request();
        $request->setControllerObjectName(HelpCommandController::class);

        if (is_array($commandLine) === true) {
            $rawCommandLineArguments = $commandLine;
        } else {
            preg_match_all(self::ARGUMENT_MATCHING_EXPRESSION, $commandLine, $commandLineMatchings, PREG_SET_ORDER);
            $rawCommandLineArguments = [];
            foreach ($commandLineMatchings as $match) {
                if (isset($match['NoQuotes'])) {
                    $rawCommandLineArguments[] = str_replace(['\ ', '\"', "\\'", '\\\\'], [
                        ' ',
                        '"',
                        "'",
                        '\\'
                    ], $match['NoQuotes']);
                } elseif (isset($match['DoubleQuotes'])) {
                    $rawCommandLineArguments[] = str_replace('\\"', '"', $match['DoubleQuotes']);
                } elseif (isset($match['SingleQuotes'])) {
                    $rawCommandLineArguments[] = str_replace('\\\'', '\'', $match['SingleQuotes']);
                } else {
                    throw new InvalidArgumentNameException(sprintf('Could not parse the command line "%s" - specifically the part "%s".', $commandLine, $match[0]));
                }
            }
        }
        if (count($rawCommandLineArguments) === 0) {
            $request->setControllerCommandName('helpStub');

            return $request;
        }
        $commandIdentifier = trim(array_shift($rawCommandLineArguments));
        try {
            $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
        } catch (CommandException $exception) {
            $request->setArgument('exception', $exception);
            $request->setControllerCommandName('error');

            return $request;
        }
        $controllerObjectName = $this->objectManager->getObjectNameByClassName($command->getControllerClassName());
        $controllerCommandName = $command->getControllerCommandName();
        $request->setControllerObjectName($controllerObjectName);
        $request->setControllerCommandName($controllerCommandName);

        list($commandLineArguments, $exceedingCommandLineArguments) = $this->parseRawCommandLineArguments($rawCommandLineArguments, $controllerObjectName, $controllerCommandName);
        $request->setArguments($commandLineArguments);
        $request->setExceedingArguments($exceedingCommandLineArguments);

        return $request;
    }

    /**
     * Takes an array of unparsed command line arguments and options and converts it separated
     * by named arguments, options and unnamed arguments.
     *
     * @param array $rawCommandLineArguments The unparsed command parts (such as "--foo") as an array
     * @param string $controllerObjectName Object name of the designated command controller
     * @param string $controllerCommandName Command name of the recognized command (ie. method name without "Command" suffix)
     * @return array All and exceeding command line arguments
     * @throws InvalidArgumentMixingException
     */
    protected function parseRawCommandLineArguments(
        array $rawCommandLineArguments,
        string $controllerObjectName,
        string $controllerCommandName
    ): array {
        $commandLineArguments = [];
        $exceedingArguments = [];
        $commandMethodName = $controllerCommandName . 'Command';
        $commandMethodParameters = $this->commandManager->getCommandMethodParameters(
            $controllerObjectName,
            $commandMethodName
        );

        $requiredArgumentNames = [];
        $requiredArguments = [];
        $optionalArguments = [];
        foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
            if ($parameterInfo['optional'] === false) {
                $requiredArguments[strtolower($parameterName)] = [
                    'parameterName' => $parameterName,
                    'type' => $parameterInfo['type']
                ];
                $requiredArgumentNames[strtolower($parameterName)] = $parameterName;
            } else {
                $optionalArguments[strtolower($parameterName)] = [
                    'parameterName' => $parameterName,
                    'type' => $parameterInfo['type']
                ];
            }
        }

        $decidedToUseNamedArguments = false;
        $decidedToUseUnnamedArguments = false;
        while (count($rawCommandLineArguments) > 0) {
            $rawArgument = array_shift($rawCommandLineArguments);

            if ($rawArgument !== '' && $rawArgument[0] === '-') {
                if ($rawArgument[1] === '-') {
                    $rawArgument = substr($rawArgument, 2);
                } else {
                    $rawArgument = substr($rawArgument, 1);
                }
                $argumentName = $this->extractArgumentNameFromCommandLinePart($rawArgument);

                $parameterNameForArrayType = $this->findParameterNameForArrayArgument(
                    $argumentName,
                    $optionalArguments,
                    $requiredArguments
                );
                $pathAsArray = [];
                $matches = [];
                // if a command controller parameter name with type array is found, parse the dot syntax
                if (!empty($parameterNameForArrayType) &&
                    0 < \preg_match_all('/([^\."\']+)|"([^"]*)"|\'([^\']*)\'/', $argumentName, $matches)
                ) {
                    // replace the associative argument name with the real controller parameter name
                    $argumentName = $parameterNameForArrayType;
                    // generate the path into the associative array
                    $pathAsArray = $matches[1];
                    foreach ($pathAsArray as $k => $v) {
                        if (empty($v)) {
                            $pathAsArray[$k] = $matches[2][$k];
                        }
                    }

                    // remove the actual command line controller parameter name
                    \array_shift($pathAsArray);
                }

                if (isset($optionalArguments[$argumentName])) {
                    $argumentValue = $this->getValueOfCurrentCommandLineOption(
                        $rawArgument,
                        $rawCommandLineArguments,
                        $optionalArguments[$argumentName]['type']
                    );
                    if ($optionalArguments[$argumentName]['type'] === 'array') {
                        $this->setCommandLineArrayArgument(
                            $commandLineArguments,
                            $optionalArguments[$argumentName],
                            $argumentValue,
                            $pathAsArray
                        );
                    } else {
                        $commandLineArguments[$optionalArguments[$argumentName]['parameterName']] = $argumentValue;
                    }
                } elseif (isset($requiredArguments[$argumentName])) {
                    if ($decidedToUseUnnamedArguments) {
                        throw new InvalidArgumentMixingException(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
                    }
                    $decidedToUseNamedArguments = true;
                    $argumentValue = $this->getValueOfCurrentCommandLineOption(
                        $rawArgument,
                        $rawCommandLineArguments,
                        $requiredArguments[$argumentName]['type']
                    );
                    if ($requiredArguments[$argumentName]['type'] === 'array') {
                        $this->setCommandLineArrayArgument(
                            $commandLineArguments,
                            $requiredArguments[$argumentName],
                            $argumentValue,
                            $pathAsArray
                        );
                    } else {
                        $commandLineArguments[$requiredArguments[$argumentName]['parameterName']] = $argumentValue;
                    }
                    unset($requiredArgumentNames[strtolower($requiredArguments[$argumentName]['parameterName'])]);
                }
            } elseif (count($requiredArgumentNames) > 0) {
                if ($decidedToUseNamedArguments) {
                    throw new InvalidArgumentMixingException(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
                }
                $argumentName = array_shift($requiredArgumentNames);
                $commandLineArguments[$argumentName] = $rawArgument;
                $decidedToUseUnnamedArguments = true;
            } else {
                $exceedingArguments[] = $rawArgument;
            }
        }

        return [$commandLineArguments, $exceedingArguments];
    }

    /**
     * Returns the command line controller parameter name for a possible associative array argument
     *
     * @param string $argumentName The command line argument name
     * @param array $optionalArguments Parsed command controller optional arguments
     * @param array $requiredArguments Parsed command controller required arguments
     *
     * @return string|null The parameter name or null if no associative name could be parsed
     */
    protected function findParameterNameForArrayArgument(
        string $argumentName,
        array $optionalArguments,
        array $requiredArguments
    ) : ?string {
        $argumentStartsWithParameterNameAndIsArray = static function ($arguments) use ($argumentName) {
            $filteredArguments = \array_filter(
                $arguments,
                static function ($parameterValue, $parameterName) use (&$argumentName) {
                    return
                        \str_starts_with($argumentName, $parameterName . '.') &&
                        $parameterValue['type'] === 'array'
                        ;
                },
                ARRAY_FILTER_USE_BOTH
            );

            if (!empty($filteredArguments)) {
                return \array_keys($filteredArguments)[0];
            }

            return null;
        };

        return
            $argumentStartsWithParameterNameAndIsArray($optionalArguments) ??
            $argumentStartsWithParameterNameAndIsArray($requiredArguments);
    }

    /**
     * Set command line argument array value for optional path
     *
     * @param array $commandLineArguments The command line arguments that will be passed to the command controller
     * @param array $argument An argument configuration
     * @param $argumentValue The argument value
     * @param array $pathAsArray Optional path to set the associative array value
     *
     * @return void
     */
    protected function setCommandLineArrayArgument(
        array &$commandLineArguments,
        array $argument,
        $argumentValue,
        array $pathAsArray = []
    ) : void {
        $setArrayValueByPath = static function ($array, array $path, $value) {
            $element = &$array;
            foreach ($path as $key) {
                if (!isset($element[$key])) {
                    $element[$key] = [];
                }
                $element = &$element[$key];
            }
            $element = $value;
            return $array;
        };

        if (!isset($commandLineArguments[$argument['parameterName']])) {
            $commandLineArguments[$argument['parameterName']] = [];
        }

        if (!empty($pathAsArray)) {
            $commandLineArguments[$argument['parameterName']] = $setArrayValueByPath(
                $commandLineArguments[$argument['parameterName']],
                $pathAsArray,
                $argumentValue
            );
        } else {
            $commandLineArguments[$argument['parameterName']][] = $argumentValue;
        }
    }

    /**
     * Extracts the option or argument name from the name / value pair of a command line.
     *
     * @param string $commandLinePart Part of the command line, e.g. "my-important-option=SomeInterestingValue"
     * @return string The lowercased argument name, e.g. "myimportantoption"
     */
    protected function extractArgumentNameFromCommandLinePart(string $commandLinePart): string
    {
        $nameAndValue = explode('=', $commandLinePart, 2);

        return strtolower(str_replace('-', '', $nameAndValue[0]));
    }

    /**
     * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
     *
     * @param string $currentArgument The current argument
     * @param array &$rawCommandLineArguments Array of the remaining command line arguments
     * @param string $expectedArgumentType The expected type of the current argument, because booleans get special attention
     * @return mixed The value of the first argument
     */
    protected function getValueOfCurrentCommandLineOption(string $currentArgument, array &$rawCommandLineArguments, string $expectedArgumentType)
    {
        if ((!isset($rawCommandLineArguments[0]) && (strpos($currentArgument, '=') === false)) || (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === false))) {
            return true;
        }

        if (strpos($currentArgument, '=') === false) {
            $possibleValue = trim(array_shift($rawCommandLineArguments));
            if (strpos($possibleValue, '=') === false) {
                if ($expectedArgumentType !== 'boolean') {
                    return $possibleValue;
                }
                if (array_search($possibleValue, ['on', '1', 'y', 'yes', 'true', 'TRUE']) !== false) {
                    return true;
                }
                if (array_search($possibleValue, ['off', '0', 'n', 'no', 'false', 'FALSE']) !== false) {
                    return false;
                }
                array_unshift($rawCommandLineArguments, $possibleValue);

                return true;
            }
            $currentArgument .= $possibleValue;
        }

        $splitArgument = explode('=', $currentArgument, 2);
        while ((!isset($splitArgument[1]) || trim($splitArgument[1]) === '') && count($rawCommandLineArguments) > 0) {
            $currentArgument .= array_shift($rawCommandLineArguments);
            $splitArgument = explode('=', $currentArgument);
        }

        return $splitArgument[1] ?? '';
    }
}
