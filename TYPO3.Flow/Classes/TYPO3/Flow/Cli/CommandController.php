<?php
namespace TYPO3\Flow\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\Argument;
use TYPO3\Flow\Mvc\Controller\ControllerInterface;
use TYPO3\Flow\Mvc\Controller\Arguments;
use TYPO3\Flow\Mvc\Exception\CommandException;
use TYPO3\Flow\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\Flow\Mvc\Exception\NoSuchCommandException;
use TYPO3\Flow\Mvc\Exception\StopActionException;
use TYPO3\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\Flow\Mvc\RequestInterface;
use TYPO3\Flow\Mvc\ResponseInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * A controller which processes requests from the command line
 *
 * @Flow\Scope("singleton")
 */
class CommandController implements ControllerInterface {

	/**
	 * @var Request
	 * @api
	 */
	protected $request;

	/**
	 * @var Response
	 * @api
	 */
	protected $response;

	/**
	 * @var Arguments
	 * @api
	 */
	protected $arguments;

	/**
	 * Name of the command method
	 *
	 * @var string
	 * @api
	 */
	protected $commandMethodName = '';

	/**
	 * @var ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var ConsoleOutput
	 * @api
	 */
	protected $output;

	/**
	 * Constructs the command controller
	 */
	public function __construct() {
		$this->arguments = new Arguments(array());
		$this->output = new ConsoleOutput();
	}

	/**
	 * Injects the reflection service
	 *
	 * @param ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Processes a command line request.
	 *
	 * @param RequestInterface $request The request object
	 * @param ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @throws UnsupportedRequestTypeException if the controller doesn't support the current request type
	 * @api
	 */
	public function processRequest(RequestInterface $request, ResponseInterface $response) {
		if (!$request instanceof Request) {
			throw new UnsupportedRequestTypeException(sprintf('%s only supports command line requests – requests of type "%s" given.', get_class($this), get_class($request)), 1300787096);
		}

		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;

		$this->commandMethodName = $this->resolveCommandMethodName();
		$this->initializeCommandMethodArguments();
		$this->mapRequestArgumentsToControllerArguments();
		$this->callCommandMethod();
	}

	/**
	 * Resolves and checks the current command method name
	 *
	 * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is case insensitive regarding method names.
	 *
	 * @return string Method name of the current command
	 * @throws NoSuchCommandException
	 */
	protected function resolveCommandMethodName() {
		$commandMethodName = $this->request->getControllerCommandName() . 'Command';
		if (!is_callable(array($this, $commandMethodName))) {
			throw new NoSuchCommandException(sprintf('A command method "%s()" does not exist in controller "%s".', $commandMethodName, get_class($this)), 1300902143);
		}
		return $commandMethodName;
	}

	/**
	 * Initializes the arguments array of this controller by creating an empty argument object for each of the
	 * method arguments found in the designated command method.
	 *
	 * @return void
	 * @throws InvalidArgumentTypeException
	 */
	protected function initializeCommandMethodArguments() {
		$this->arguments->removeAll();
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->commandMethodName);

		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, get_class($this), $this->commandMethodName), 1306755296);
			}
			$defaultValue = (isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL);
			$this->arguments->addNewArgument($parameterName, $dataType, ($parameterInfo['optional'] === FALSE), $defaultValue);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		/** @var Argument $argument */
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();

			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
				continue;
			}
			if (!$argument->isRequired()) {
				continue;
			}
			$argumentValue = NULL;
			while ($argumentValue === NULL) {
				$argumentValue = $this->ask(sprintf('<comment>Please specify the required argument "%s":</comment> ', $argumentName));
			}

			if ($argumentValue === NULL) {
				$exception = new CommandException(sprintf('Required argument "%s" is not set.', $argumentName), 1306755520);
				$this->forward('error', 'TYPO3\Flow\Command\HelpCommandController', array('exception' => $exception));
			}
			$argument->setValue($argumentValue);
		}
	}

	/**
	 * Forwards the request to another command and / or CommandController.
	 *
	 * Request is directly transferred to the other command / controller
	 * without the need for a new request.
	 *
	 * @param string $commandName
	 * @param string $controllerObjectName
	 * @param array $arguments
	 * @return void
	 * @throws StopActionException
	 */
	protected function forward($commandName, $controllerObjectName = NULL, array $arguments = array()) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerCommandName($commandName);
		if ($controllerObjectName !== NULL) {
			$this->request->setControllerObjectName($controllerObjectName);
		}
		$this->request->setArguments($arguments);

		$this->arguments->removeAll();
		throw new StopActionException();
	}

	/**
	 * Calls the specified command method and passes the arguments.
	 *
	 * If the command returns a string, it is appended to the content in the
	 * response object. If the command doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @return void
	 */
	protected function callCommandMethod() {
		$preparedArguments = array();
		/** @var Argument $argument */
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}

		$command = new Command(get_class($this), $this->request->getControllerCommandName());
		if ($command->isDeprecated()) {
			$suggestedCommandMessage = '';

			$relatedCommandIdentifiers = $command->getRelatedCommandIdentifiers();
			if ($relatedCommandIdentifiers !== array()) {
				$suggestedCommandMessage = sprintf(
					', use the following command%s instead: %s',
					count($relatedCommandIdentifiers) > 1 ? 's' : '',
					implode(', ', $relatedCommandIdentifiers)
				);
			}
			$this->outputLine('<b>Warning:</b> This command is <b>DEPRECATED</b>%s%s', array($suggestedCommandMessage, PHP_EOL));
		}

		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);

		if (is_string($commandResult) && strlen($commandResult) > 0) {
			$this->response->appendContent($commandResult);
		} elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
			$this->response->appendContent((string)$commandResult);
		}
	}

	/**
	 * Returns the CLI Flow command depending on the environment
	 *
	 * @return string
	 */
	public function getFlowInvocationString() {
		if (DIRECTORY_SEPARATOR === '/' || (isset($_SERVER['MSYSTEM']) && $_SERVER['MSYSTEM'] === 'MINGW32')) {
			return './flow';
		} else {
			return 'flow.bat';
		}
	}

	/**
	 * Outputs specified text to the console window
	 * You can specify arguments that will be passed to the text via sprintf
	 * @see http://www.php.net/sprintf
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 * @api
	 */
	protected function output($text, array $arguments = array()) {
		$this->output->output($text, $arguments);
	}

	/**
	 * Outputs specified text to the console window and appends a line break
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 * @see output()
	 * @see outputLines()
	 * @api
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		$this->output->outputLine($text, $arguments);
	}

	/**
	 * Formats the given text to fit into MAXIMUM_LINE_LENGTH and outputs it to the
	 * console window
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @param integer $leftPadding The number of spaces to use for indentation
	 * @return void
	 * @see outputLine()
	 * @api
	 */
	protected function outputFormatted($text = '', array $arguments = array(), $leftPadding = 0) {
		$this->output->outputFormatted($text, $arguments, $leftPadding);
	}

	/**
	 * Exits the CLI through the dispatcher and makes sure that Flow is properly shut down.
	 *
	 * If your command relies on functionality which is triggered through the Bootstrap
	 * shutdown (such as the persistence framework), you must use quit() instead of exit().
	 *
	 * @param integer $exitCode Exit code to return on exit (see http://www.php.net/exit)
	 * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
	 * @return void
	 */
	protected function quit($exitCode = 0) {
		$this->response->setExitCode($exitCode);
		throw new StopActionException;
	}

	/**
	 * Sends the response and exits the CLI without any further code execution
	 * Should be used for commands that flush code caches.
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 */
	protected function sendAndExit($exitCode = 0) {
		$this->response->send();
		exit($exitCode);
	}

}